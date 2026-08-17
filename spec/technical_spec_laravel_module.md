# ĐẶC TẢ KỸ THUẬT (TECHNICAL SPECIFICATION)
## Module: AI Affiliate Video Script Generator (Laravel)

### 1. Tổng quan Module (Overview)
**Mục tiêu:** Xây dựng một module trong hệ thống Laravel có khả năng nhận input là "Tên sản phẩm/Chủ đề" (đặc biệt trong ngách Mẹ & Bé). Dựa vào dữ liệu đầu vào, module sẽ tự động sinh ra một Prompt hoàn chỉnh để gửi qua API của các LLM (OpenAI, Gemini, Claude...). AI sẽ đóng vai trò như một Rule Engine để:
1. Đánh giá sản phẩm qua "Cây quyết định 5 câu hỏi".
2. Lựa chọn "Công thức kịch bản" (Content Type Format) phù hợp nhất và tuân thủ tuyệt đối các ranh giới pháp lý/đạo đức.
3. Sinh ra kịch bản chi tiết theo chuẩn định dạng của công thức đã chọn.

### 2. Kiến trúc Hệ thống (Architecture)
Module tuân thủ mô hình MVC và Service Pattern của Laravel:
- **Controllers:** `ScriptGeneratorController` (Tiếp nhận request từ UI/API).
- **Services:** 
  - `PromptBuilderService`: Chịu trách nhiệm lắp ghép bối cảnh, cây quyết định và thông tin sản phẩm thành một Prompt tối ưu.
  - `LLMIntegrationService`: Gọi API LLM (OpenAI/Gemini), xử lý timeout, retry và parse kết quả trả về dạng JSON.
- **Repositories (Tùy chọn):** Quản lý lưu trữ lịch sử tạo kịch bản.

### 3. Cấu trúc Database (Database Schema)

Mặc dù AI chịu trách nhiệm chính, nhưng việc lưu trữ cấu trúc công thức ở Database giúp dễ dàng cấu hình mà không cần sửa code.

**Table: `script_formulas` (Danh mục các công thức)**
- `id` (PK)
- `code` (varchar): Mã công thức (PSC, BAB, HVC, ABCD, TESTIMONIAL, ONBOARDING).
- `name` (varchar): Tên công thức.
- `structure_json` (json): Cấu trúc các phần (vd: `["Before", "Challenge", "Solution", "Result", "Recommendation"]`).
- `rules` (text): Các lưu ý/ranh giới bắt buộc (vd: Không dùng hình ảnh trẻ khóc để tạo áp lực cho PSC).

**Table: `generated_scripts` (Lịch sử kịch bản đã tạo)**
- `id` (PK)
- `user_id` (FK): Người tạo.
- `product_topic` (varchar): Tên sản phẩm/chủ đề đầu vào.
- `applied_formula` (varchar): Công thức AI đã chọn.
- `reasoning` (text): Lý do AI chọn công thức này (giải thích qua 5 câu hỏi).
- `script_content` (json/text): Nội dung kịch bản chi tiết.
- `status` (enum): `draft`, `approved`, `rejected`.
- `created_at`, `updated_at`

### 4. Thiết kế Prompt cốt lõi (Core Prompt Engineering)

Đây là "trái tim" của module. Bất kể user nhập chủ đề gì, `PromptBuilderService` sẽ render ra một Master Prompt theo cấu trúc Zero-shot hoặc Few-shot với Chain-of-Thought.

#### Mẫu Prompt System (Hệ thống truyền cho AI):

```text
Bạn là một chuyên gia Copywriter chuyên nghiệp mảng Affiliate Video ngắn (TikTok/Reels), đặc biệt trong ngách Mẹ & Bé.

Nhiệm vụ của bạn là nhận một sản phẩm/chủ đề, sau đó thực hiện 2 BƯỚC theo nguyên tắc khắt khe dưới đây.

BƯỚC 1: LỰA CHỌN CÔNG THỨC (CONTENT TYPE FORMAT)
Sử dụng Cây quyết định sau để chọn 1 trong 6 công thức (PSC, BAB, HVC, ABCD, Testimonial, Onboarding). Áp dụng theo thứ tự, dừng ở câu đầu tiên cho ra kết quả:
1. Sản phẩm có thuộc nhóm CẤM/HẠN CHẾ quảng cáo không? (sữa <24 tháng, bình bú, ti giả, TPCN, thuốc). Nếu CÓ -> Chọn ONBOARDING hoặc HVC (Bỏ hoàn toàn CTA bán hàng).
2. Dùng sai có gây hậu quả cho bé/mẹ không? (nhộng chũn, hút mũi, ghế ô tô...). Nếu CÓ -> Chọn ONBOARDING (Tập trung phần Mistakes).
3. Giá cao (>800k) HOẶC cần nhiều cân nhắc? Nếu CÓ -> Chọn TESTIMONIAL.
4. Thay đổi có quan sát được bằng mắt trong 1 khung hình không? (vết bẩn, thấm hút). Nếu CÓ -> Chọn BAB. Lưu ý: KHÔNG dùng ảnh da em bé trước/sau.
5. Khán giả đã tự biết mình có vấn đề chưa? Nếu RỒI -> Chọn PSC. Nếu CHƯA (cần giáo dục) -> Chọn HVC.

BƯỚC 2: VIẾT KỊCH BẢN
Dựa vào công thức đã chọn ở Bước 1, hãy viết kịch bản chi tiết.
- Độ dài: Phù hợp video 30 - 90 giây.
- Hình thức: Bảng gồm 3 cột (Thời lượng/Nhịp | Hình ảnh/Video Shot | Lời thoại/Audio).
- RANH GIỚI BẮT BUỘC:
  + Không dùng hình ảnh trẻ khóc/đau để tạo áp lực.
  + Không dùng Before/After trên cơ thể trẻ em.
  + TPCN/Da liễu: Không cam kết chữa bệnh, chỉ chia sẻ dưới góc độ trải nghiệm cá nhân.

OUTPUT FORMAT YÊU CẦU (Trả về định dạng JSON thuần túy):
{
  "selected_formula": "Tên công thức",
  "reasoning": "Giải thích ngắn gọn tại sao chọn công thức này dựa trên 5 câu hỏi",
  "script": "Nội dung kịch bản định dạng Markdown dạng bảng"
}
```

#### Mẫu Prompt User (Được sinh ra khi User nhập liệu):
```text
Sản phẩm/Chủ đề cần làm video: "{user_input_topic}"
Hãy phân tích sản phẩm này, chọn công thức chuẩn xác và viết kịch bản.
```

### 5. Triển khai Code (Code Implementation Snippets)

**5.1. PromptBuilderService.php**
```php
namespace App\Services;

class PromptBuilderService
{
    public function buildPrompt(string $topic): array
    {
        $systemPrompt = $this->getSystemPrompt(); // Chứa toàn bộ Rule & Logic cây quyết định
        $userPrompt = "Sản phẩm/Chủ đề cần làm video: '{$topic}'. Hãy phân tích sản phẩm này, chọn công thức chuẩn xác và viết kịch bản trả về JSON.";

        return [
            'system' => $systemPrompt,
            'user' => $userPrompt
        ];
    }
}
```

**5.2. LLMIntegrationService.php**
```php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LLMIntegrationService
{
    protected string $apiUrl = 'https://api.openai.com/v1/chat/completions'; // Hoặc Gemini endpoint
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.openai.key');
    }

    public function generateScript(array $prompts): ?array
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(60)
            ->post($this->apiUrl, [
                'model' => 'gpt-4o-mini', // Nên dùng model thông minh để hiểu logic logic cây quyết định
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $prompts['system']],
                    ['role' => 'user', 'content' => $prompts['user']]
                ],
                'temperature' => 0.7,
            ]);

        if ($response->successful()) {
            $jsonString = $response->json('choices.0.message.content');
            return json_decode($jsonString, true);
        }

        Log::error('LLM API Error', $response->json());
        return null;
    }
}
```

**5.3. ScriptGeneratorController.php**
```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PromptBuilderService;
use App\Services\LLMIntegrationService;
use App\Models\GeneratedScript;

class ScriptGeneratorController extends Controller
{
    public function generate(Request $request, PromptBuilderService $builder, LLMIntegrationService $llm)
    {
        $request->validate(['topic' => 'required|string|max:255']);
        $topic = $request->input('topic');

        // 1. Build prompt
        $prompts = $builder->buildPrompt($topic);

        // 2. Call AI
        $result = $llm->generateScript($prompts);

        if (!$result) {
            return response()->json(['error' => 'AI Generation failed'], 500);
        }

        // 3. Save to DB
        $script = GeneratedScript::create([
            'user_id' => auth()->id(),
            'product_topic' => $topic,
            'applied_formula' => $result['selected_formula'],
            'reasoning' => $result['reasoning'],
            'script_content' => $result['script'],
            'status' => 'draft'
        ]);

        return response()->json([
            'message' => 'Script generated successfully',
            'data' => $script
        ]);
    }
}
```

### 6. Luồng hoạt động User (User Workflow)
1. User vào màn hình (Frontend), nhập vào ô input: `"Nước rửa bình sữa cho trẻ sơ sinh"`.
2. Frontend gọi API `POST /api/scripts/generate`.
3. Laravel truyền chủ đề này vào Prompt, yêu cầu AI đóng vai trò chuyên gia, chạy qua cây quyết định.
4. AI nhận diện "Nước rửa bình sữa" thuộc nhóm khán giả "đã có vấn đề rõ ràng (PSC) hoặc cần hướng dẫn (Onboarding)". Dựa vào Master Prompt, AI trả về kết quả JSON chọn công thức `PSC`.
5. Frontend nhận JSON và hiển thị:
   - **Chủ đề:** Nước rửa bình sữa
   - **Format Khuyên Dùng:** PSC (Problem - Solution - CTA)
   - **Lý do AI chọn:** "Khán giả đã nhận thức rõ vấn đề mùi hôi và cặn sữa ở đáy bình. Sản phẩm không cấm quảng cáo, giá không quá cao."
   - **Kịch bản:** (Bảng chi tiết do AI sinh ra).