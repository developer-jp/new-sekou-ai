-- Add Gemini 3 Flash and Pro models
-- Run with: mysql -u username -p database_name < add_gemini_3_models.sql

INSERT INTO ai_models (name, provider, model_id, description, max_tokens, context_window, input_price, output_price, is_active, supports_vision, supports_function_calling, supports_streaming, sort_order, created_at, updated_at)
VALUES 
('Gemini 3 Flash', 'google', 'gemini-3-flash-preview', 'Google最新のGemini 3モデル。高速推論と複雑なエージェントワークフローに最適。', 65536, 1048576, 0.10, 0.40, 1, 1, 1, 1, 20, NOW(), NOW()),
('Gemini 3 Pro', 'google', 'gemini-3-pro-preview', 'Google最高性能のGemini 3モデル。高度な推論、コーディング、画像生成に対応。', 65536, 1048576, 1.25, 5.00, 1, 1, 1, 1, 21, NOW(), NOW());

