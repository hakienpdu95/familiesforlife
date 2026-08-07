<?php

namespace Modules\PromptFrameworkStudio\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * spec/PromptFrameworkStudio_Technical_Specification.md §6 — quyền gate phẳng bằng permission
 * string 'prompt_framework_studio.use' đọc trực tiếp ở routes/web.php (middleware 'can:'), KHÔNG
 * cần Gate::define riêng ở đây — cùng nguyên tắc ContentOutlinesServiceProvider.
 */
class PromptFrameworkStudioServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'PromptFrameworkStudio';

    protected string $nameLower = 'promptframeworkstudio';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        // spec/PromptFrameworkStudio_Technical_Specification.md §0/§2 — toàn bộ spec (và code
        // dưới đây) đọc config qua key phẳng 'prompt_framework_studio' (snake_case, khớp tên
        // file). Auto-merge mặc định của ModuleServiceProvider::registerConfig() (chạy trong
        // parent::boot() ở trên) suy ra key từ 'nameLower.tên_file' = 'promptframeworkstudio.
        // prompt_framework_studio' — KHÔNG khớp key spec dùng. Merge tay thêm 1 lần ở đúng key
        // cần dùng; key sai do auto-merge tạo ra vẫn tồn tại nhưng không được tham chiếu ở đâu
        // (vô hại, không xoá được vì registerConfig() là private/không có hook tắt riêng lẻ).
        $this->mergeConfigFrom(module_path($this->name, 'config/prompt_framework_studio.php'), 'prompt_framework_studio');
    }
}
