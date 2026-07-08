<?php

// Không còn route web — admin authoring UI (dashboard/business-blueprint/admin) đã bị xoá.
// Model Blueprint/BlueprintVersion + các model con (capability/workflow/phase/checklist/
// deployment-role/sidebar-item...) vẫn được VerticalRegistry, OrganizationSolution dùng
// trực tiếp ở runtime — xem Modules/BusinessBlueprint/app/Models.
// TxngBlueprintSeeder (chạy trong php artisan db:seed) vẫn dùng CreateBlueprintAction +
// các Upsert*Action để seed blueprint mẫu — không đụng tới.
