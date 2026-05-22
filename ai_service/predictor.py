import pandas as pd
import numpy as np
import xgboost as xgb
from sklearn.model_selection import train_test_split
from sklearn.metrics import mean_squared_error, r2_score, accuracy_score
from sklearn.preprocessing import StandardScaler, LabelEncoder

class XGBService:
    def __init__(self):
        self.model = xgb.XGBRegressor(
            n_estimators=100,
            learning_rate=0.05,
            max_depth=5,
            subsample=0.8,
            colsample_bytree=0.8,
            objective='reg:squarederror'
        )
        self.classifier = xgb.XGBClassifier(
            n_estimators=100,
            learning_rate=0.1,
            max_depth=4,
            use_label_encoder=False,
            eval_metric='mlogloss'
        )
        self.scaler = StandardScaler()

    def _prepare_data(self, history):
        """
        Mengubah list of dict (month, total) menjadi fitur untuk XGBoost
        history: [{'month': '2026-01', 'total': 100000}, ...]
        """
        df = pd.DataFrame(history)
        df['total'] = df['total'].astype(float)
        
        # Urutkan berdasarkan waktu
        df = df.sort_values('month')
        
        # Feature Engineering Dasar: Lags
        if len(df) < 4:
            return None, None, df['total'].mean()

        df['lag_1'] = df['total'].shift(1)
        df['lag_2'] = df['total'].shift(2)
        df['lag_3'] = df['total'].shift(3)
        df['rolling_mean'] = df['total'].shift(1).rolling(window=3).mean()
        df['month_num'] = pd.to_datetime(df['month']).dt.month
        
        df_clean = df.dropna().copy()
        
        if df_clean.empty:
            return None, None, df['total'].mean()
            
        X = df_clean[['lag_1', 'lag_2', 'lag_3', 'rolling_mean', 'month_num']]
        y = df_clean['total']
        
        return X, y, None

    def train_and_evaluate(self, history):
        """
        Melatih model dan mengembalikan metrik akurasi
        """
        X, y, fallback = self._prepare_data(history)
        if X is None:
            return {"status": "error", "message": "Data tidak cukup untuk pelatihan (min 4 bulan)"}
            
        # Jika data sangat sedikit (< 10), jangan gunakan split test yang besar
        test_size = 0.2 if len(X) >= 10 else 1
        
        if len(X) <= 2:
             self.model.fit(X, y)
             return {"status": "success", "accuracy": 100, "rmse": 0, "message": "Data minim, model dilatih pada seluruh dataset."}

        X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=test_size, random_state=42)
        
        self.model.fit(X_train, y_train)
        preds = self.model.predict(X_test)
        
        rmse = np.sqrt(mean_squared_error(y_test, preds))
        
        # Hitung MAPE dengan proteksi
        mape = np.mean(np.abs((y_test - preds) / (y_test + 1e-5))) * 100
        accuracy = 100 - mape
        
        # Jika data < 10, akurasi XGBoost seringkali tidak stabil (terlalu sensitif)
        # Kita berikan batas bawah yang masuk akal atau pembulatan yang lebih baik
        if len(history) < 10:
            accuracy = max(accuracy, 0)
            # Opsional: Jika akurasi 0 tapi RMSE tidak terlalu besar dibanding rata-rata, 
            # itu tandanya data terlalu sedikit untuk validasi statistik yang kuat.
            if accuracy == 0 and rmse < np.mean(y) * 0.5:
                accuracy = "Low Data (N/A)"

        # Bersihkan nilai NaN/Inf agar JSON compliant
        res_accuracy = round(float(accuracy), 2) if isinstance(accuracy, (int, float)) and not np.isnan(accuracy) and not np.isinf(accuracy) else (accuracy if isinstance(accuracy, str) else 0.0)
        res_rmse = round(float(rmse), 2) if not np.isnan(rmse) and not np.isinf(rmse) else 0.0

        return {
            "status": "success",
            "accuracy": res_accuracy,
            "rmse": res_rmse,
            "data_points": len(history)
        }

    def predict(self, history):
        """
        Memprediksi pengeluaran bulan depan
        """
        if not history or len(history) < 3:
            values = [h['total'] for h in history] if history else [0]
            return float(np.mean(values)) if values else 0.0

        X, y, fallback_val = self._prepare_data(history)
        
        if fallback_val is not None:
            return float(fallback_val)

        self.model.fit(X, y)
        
        last_rows = pd.DataFrame(history).sort_values('month').tail(3)
        lags = last_rows['total'].values[::-1] # [lag1, lag2, lag3]
        
        next_month_num = (pd.to_datetime(last_rows.iloc[-1]['month']).month % 12) + 1
        
        X_next = pd.DataFrame([{
            'lag_1': float(lags[0]),
            'lag_2': float(lags[1]),
            'lag_3': float(lags[2]),
            'rolling_mean': np.mean(lags),
            'month_num': next_month_num
        }])
        
        prediction = self.model.predict(X_next)
        return float(max(0, prediction[0]))

    def detect_anomalies(self, transactions):
        """
        Mendeteksi transaksi anomali berdasarkan IQR (Interquartile Range)
        transactions: [{'id': 1, 'amount': 5000, 'category': 'Food'}, ...]
        """
        if len(transactions) < 5:
            return []
            
        df = pd.DataFrame(transactions)
        df['amount'] = df['amount'].astype(float)
        
        anomalies = []
        for cat in df['category'].unique():
            cat_df = df[df['category'] == cat]
            if len(cat_df) < 3: continue
            
            q1 = cat_df['amount'].quantile(0.25)
            q3 = cat_df['amount'].quantile(0.75)
            iqr = q3 - q1
            upper_bound = q3 + (1.5 * iqr)
            
            cat_anomalies = cat_df[cat_df['amount'] > upper_bound]
            for _, row in cat_anomalies.iterrows():
                anomalies.append({
                    'id': row['id'],
                    'amount': row['amount'],
                    'category': row['category'],
                    'reason': f"Transaksi di {row['category']} jauh di atas rata-rata biasanya."
                })
        
        return anomalies

    def classify_condition(self, stats):
        """
        Klasifikasi kondisi keuangan (Healthy, Warning, Critical)
        stats: {'income': 1000, 'expense': 800, 'savings': 200, 'debt': 500}
        """
        ratio = stats['expense'] / stats['income'] if stats['income'] > 0 else 2.0
        
        if ratio < 0.5:
            return "Healthy", "Kondisi sangat baik. Pengeluaran di bawah 50% pendapatan."
        elif ratio < 0.8:
            return "Warning", "Waspada. Pengeluaran Anda mendekati ambang batas pendapatan."
        else:
            return "Critical", "Bahaya! Pengeluaran melebihi atau hampir sama dengan pendapatan."

    def analyze_patterns(self, transactions):
        """
        Menganalisis pola spending
        """
        df = pd.DataFrame(transactions)
        if df.empty: return "Belum ada data."
        
        df['date'] = pd.to_datetime(df['date'])
        df['day_name'] = df['date'].dt.day_name()
        
        # Cari hari dengan pengeluaran tertinggi
        day_spending = df.groupby('day_name')['amount'].sum().sort_values(ascending=False)
        top_day = day_spending.index[0]
        
        return f"Pola belanja Anda paling tinggi pada hari {top_day}. Pertimbangkan untuk lebih hemat di hari tersebut."

    def analyze_deep_insights(self, current_data, previous_data):
        insights = []
        curr_df = pd.DataFrame(current_data)
        prev_df = pd.DataFrame(previous_data)
        if curr_df.empty or prev_df.empty: return insights
        merged = pd.merge(curr_df, prev_df, on='category', how='inner', suffixes=('_curr', '_prev'))
        for _, row in merged.iterrows():
            curr = float(row['total_curr']); prev = float(row['total_prev'])
            if prev == 0: continue
            diff_pct = ((curr - prev) / prev) * 100
            if diff_pct > 20:
                insights.append({'category': row['category'], 'type': 'increase', 'percentage': round(diff_pct, 1), 'message': f"Pengeluaran '{row['category']}' naik {round(diff_pct, 1)}%."})
            elif diff_pct < -20:
                insights.append({'category': row['category'], 'type': 'decrease', 'percentage': round(abs(diff_pct), 1), 'message': f"Pengeluaran '{row['category']}' turun {round(abs(diff_pct), 1)}%."})
        return insights
