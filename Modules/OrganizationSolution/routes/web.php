<?php

// Không còn route web — UI kích hoạt/wizard cấu hình (dashboard/organization-solutions) và
// Modules\SolutionCatalog/Modules\Deployment (từng dùng Features/SolutionActivation) đều đã
// bị xoá. Model OrganizationSolution + OrganizationChecklistConfig vẫn được VerticalRegistry
// (đọc trạng thái deploy) và lệnh organization-solution:migrate-org-vertical-templates dùng
// trực tiếp — xem Modules/OrganizationSolution/app/Models, app/Console/Commands.
