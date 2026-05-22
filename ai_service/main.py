from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import List, Optional
from predictor import XGBService
import uvicorn

app = FastAPI(title="Finance AI Service (XGBoost)")
predictor = XGBService()

class HistoryItem(BaseModel):
    month: str
    total: float

class PredictionRequest(BaseModel):
    history: List[HistoryItem]

class CategoryItem(BaseModel):
    category: str
    total: float

class InsightRequest(BaseModel):
    current_month: List[CategoryItem]
    previous_month: List[CategoryItem]

class TransactionItem(BaseModel):
    id: int
    amount: float
    category: str
    date: str

class AnomalyRequest(BaseModel):
    transactions: List[TransactionItem]

class StatsRequest(BaseModel):
    income: float
    expense: float
    savings: float
    debt: float
    transactions: List[TransactionItem]

@app.get("/")
def home():
    return {"message": "Finance AI Service is running", "method": "XGBoost"}

@app.post("/train")
def train_model(request: PredictionRequest):
    try:
        history_data = [item.model_dump() for item in request.history]
        result = predictor.train_and_evaluate(history_data)
        return result
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/predict")
def predict_expense(request: PredictionRequest):
    try:
        history_data = [item.model_dump() for item in request.history]
        if not history_data:
            raise HTTPException(status_code=400, detail="History data cannot be empty")
        prediction_val = predictor.predict(history_data)
        last_val = history_data[-1]['total'] if history_data else 0
        trend = "stable"
        if prediction_val > last_val * 1.05: trend = "up"
        elif prediction_val < last_val * 0.95: trend = "down"
        return {"prediction": round(prediction_val, 2), "trend": trend, "data_points": len(history_data), "algorithm": "XGBoost"}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/anomalies")
def get_anomalies(request: AnomalyRequest):
    try:
        data = [item.dict() for item in request.transactions]
        anomalies = predictor.detect_anomalies(data)
        return {"anomalies": anomalies}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/financial_report")
def get_financial_report(request: StatsRequest):
    try:
        stats = {"income": request.income, "expense": request.expense, "savings": request.savings, "debt": request.debt}
        condition, advice = predictor.classify_condition(stats)
        
        transactions_data = [item.dict() for item in request.transactions]
        pattern = predictor.analyze_patterns(transactions_data)
        
        # Risk prediction (simple logic for now)
        risk_level = "Low"
        if stats['expense'] > stats['income']: risk_level = "High"
        elif stats['debt'] > stats['income'] * 3: risk_level = "Medium"
        
        return {
            "condition": condition,
            "condition_message": advice,
            "spending_pattern": pattern,
            "risk_level": risk_level,
            "status": "success"
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/analyze_deep_insights")
def analyze_insights(request: InsightRequest):
    try:
        curr_data = [item.dict() for item in request.current_month]
        prev_data = [item.dict() for item in request.previous_month]
        insights = predictor.analyze_deep_insights(curr_data, prev_data)
        return {"insights": insights, "status": "success"}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=8001)
