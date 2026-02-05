<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiModelsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $models = [
            // OpenAI Models
            [
                'name' => 'GPT-4o',
                'provider' => 'openai',
                'model_id' => 'gpt-4o',
                'description' => 'OpenAI最新のフラッグシップモデル。テキストと画像を理解可能。',
                'max_tokens' => 16384,
                'context_window' => 128000,
                'input_price' => 2.50,
                'output_price' => 10.00,
                'is_active' => true,
                'supports_vision' => true,
                'supports_function_calling' => true,
                'supports_streaming' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'GPT-4o Mini',
                'provider' => 'openai',
                'model_id' => 'gpt-4o-mini',
                'description' => 'コスト効率の高い軽量モデル。日常タスクに最適。',
                'max_tokens' => 16384,
                'context_window' => 128000,
                'input_price' => 0.15,
                'output_price' => 0.60,
                'is_active' => true,
                'supports_vision' => true,
                'supports_function_calling' => true,
                'supports_streaming' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'o1',
                'provider' => 'openai',
                'model_id' => 'o1',
                'description' => '複雑な推論タスクに特化した思考モデル。',
                'max_tokens' => 100000,
                'context_window' => 200000,
                'input_price' => 15.00,
                'output_price' => 60.00,
                'is_active' => true,
                'supports_vision' => true,
                'supports_function_calling' => true,
                'supports_streaming' => true,
                'sort_order' => 3,
            ],

            // Anthropic Models
            [
                'name' => 'Claude 3.5 Sonnet',
                'provider' => 'anthropic',
                'model_id' => 'claude-3-5-sonnet-20241022',
                'description' => 'Anthropicの最も賢いモデル。コーディングと分析に優れる。',
                'max_tokens' => 8192,
                'context_window' => 200000,
                'input_price' => 3.00,
                'output_price' => 15.00,
                'is_active' => true,
                'supports_vision' => true,
                'supports_function_calling' => true,
                'supports_streaming' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'Claude 3.5 Haiku',
                'provider' => 'anthropic',
                'model_id' => 'claude-3-5-haiku-20241022',
                'description' => '高速で効率的なモデル。即座の応答が必要な場面に。',
                'max_tokens' => 8192,
                'context_window' => 200000,
                'input_price' => 0.80,
                'output_price' => 4.00,
                'is_active' => true,
                'supports_vision' => true,
                'supports_function_calling' => true,
                'supports_streaming' => true,
                'sort_order' => 11,
            ],

            // Google Models
            [
                'name' => 'Gemini 2.0 Flash',
                'provider' => 'google',
                'model_id' => 'gemini-2.0-flash',
                'description' => 'Googleの最新高速モデル。マルチモーダル対応。',
                'max_tokens' => 8192,
                'context_window' => 1000000,
                'input_price' => 0.075,
                'output_price' => 0.30,
                'is_active' => true,
                'supports_vision' => true,
                'supports_function_calling' => true,
                'supports_streaming' => true,
                'sort_order' => 20,
            ],
            [
                'name' => 'Gemini 1.5 Pro',
                'provider' => 'google',
                'model_id' => 'gemini-1.5-pro',
                'description' => '長大なコンテキストを処理可能な高性能モデル。',
                'max_tokens' => 8192,
                'context_window' => 2000000,
                'input_price' => 1.25,
                'output_price' => 5.00,
                'is_active' => true,
                'supports_vision' => true,
                'supports_function_calling' => true,
                'supports_streaming' => true,
                'sort_order' => 21,
            ],
        ];

        foreach ($models as $model) {
            DB::table('ai_models')->updateOrInsert(
                ['provider' => $model['provider'], 'model_id' => $model['model_id']],
                array_merge($model, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
