<?php
namespace Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Customer\Actions\CreateCustomerAction;
use Modules\Customer\Actions\DeleteCustomerAction;
use Modules\Customer\Actions\UpdateCustomerAction;
use Modules\Customer\Data\Requests\StoreCustomerData;
use Modules\Customer\Data\Requests\UpdateCustomerData;
use Modules\Customer\Enums\CompanySize;
use Modules\Customer\Enums\CustomerLifecycleStage;
use Modules\Customer\Enums\CustomerType;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerFieldDefinition;
use Modules\Customer\Models\CustomerTag;
use Modules\Customer\Queries\GetCustomerHandler;
use Modules\Customer\Queries\GetCustomerQuery;
use App\Models\Province;
use Modules\Employee\Enums\EmployeeStatus;
use Modules\Employee\Models\Employee;
use Modules\LeadSource\Models\LeadSource;

class CustomerController extends Controller
{
    private function _resolveOrganizations(): array
    {
        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            return [Organization::where('id', $userOrgId)->get(['id', 'name']), $userOrgId, true];
        }
        return [Organization::orderBy('name')->get(['id', 'name']), null, false];
    }

    public function index(): View
    {
        $this->authorize('viewAny', Customer::class);

        $orgId   = TenantContext::getOrganizationId();
        $sources = LeadSource::where('is_active', true)->orderBy('sort_order')->get(['id', 'label']);
        $tags    = CustomerTag::where('organization_id', $orgId)->orderBy('name')->get(['id', 'name', 'color']);
        $stages  = collect(CustomerLifecycleStage::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]);
        $types   = collect(CustomerType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]);

        [$organizations, $defaultOrgId, $orgLocked] = $this->_resolveOrganizations();

        // Single query merges all stat counts (giống pattern OrganizationController::index()).
        // Super-admin (không khoá org) → thống kê xuyên toàn bộ tổ chức, khớp với mặc định
        // "xem tất cả" của bảng danh sách bên dưới.
        $countsQuery = $orgLocked
            ? Customer::where('organization_id', $orgId)
            : Customer::withoutTenant();

        $counts = $countsQuery
            ->selectRaw(
                'COUNT(*) as total_all,
                 SUM(CASE WHEN lifecycle_stage = ? THEN 1 ELSE 0 END) as total_active,
                 SUM(CASE WHEN lifecycle_stage = ? THEN 1 ELSE 0 END) as total_vip,
                 SUM(CASE WHEN lifecycle_stage = ? THEN 1 ELSE 0 END) as total_churned',
                [
                    CustomerLifecycleStage::Active->value,
                    CustomerLifecycleStage::VIP->value,
                    CustomerLifecycleStage::Churned->value,
                ]
            )
            ->first();

        $totalAll     = (int) ($counts->total_all     ?? 0);
        $totalActive  = (int) ($counts->total_active  ?? 0);
        $totalVip     = (int) ($counts->total_vip     ?? 0);
        $totalChurned = (int) ($counts->total_churned ?? 0);

        return view('customer::index', compact(
            'sources', 'tags', 'stages', 'types',
            'totalAll', 'totalActive', 'totalVip', 'totalChurned',
            'organizations', 'orgLocked'
        ));
    }

    public function create(): View
    {
        $this->authorize('create', Customer::class);

        $orgId     = TenantContext::getOrganizationId();
        $sources   = LeadSource::where('is_active', true)->orderBy('sort_order')->get(['id', 'label']);
        $tags      = CustomerTag::where('organization_id', $orgId)->orderBy('name')->get(['id', 'name', 'color']);
        $sizes     = collect(CompanySize::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]);
        $stages    = collect(CustomerLifecycleStage::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]);
        $provinces = Province::where('is_active', true)->orderBy('name')->get(['province_code', 'name']);
        $fieldDefs = CustomerFieldDefinition::where('organization_id', $orgId)
            ->active()->orderBy('sort_order')->get();
        $customer  = null;

        [$organizations, $defaultOrgId, $orgLocked] = $this->_resolveOrganizations();

        // orgLocked (org cố định) → render sẵn danh sách nhân viên server-side, giống
        // hệt cách EmployeeController::create() làm với $managers. Không orgLocked
        // (super-admin) → để trống, JS cascade (_initOrgCascades) load khi chọn org.
        $assignableEmployees = $orgLocked ? $this->_assignableEmployees($orgId) : collect();

        return view('customer::_form', compact(
            'customer', 'sources', 'tags', 'sizes', 'stages', 'provinces', 'fieldDefs',
            'organizations', 'defaultOrgId', 'orgLocked', 'assignableEmployees'
        ));
    }

    private function _assignableEmployees(int $orgId)
    {
        return Employee::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', EmployeeStatus::Active->value)
            ->whereNotNull('user_id')
            ->orderBy('full_name')
            ->get(['id', 'user_id', 'full_name', 'employee_code']);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Customer::class);

        $data  = StoreCustomerData::validateAndCreate($request->all());
        $orgId = $data->organization_id ?? auth()->user()->organization_id ?? TenantContext::getOrganizationId();

        $customer = CreateCustomerAction::run($data, $orgId);

        return redirect()->route('customer.show', $customer)
            ->with('success', 'Đã tạo khách hàng thành công.');
    }

    public function show(Customer $customer): View
    {
        $this->authorize('view', $customer);

        $customer  = app(GetCustomerHandler::class)->handle(new GetCustomerQuery($customer));
        $fieldDefs = CustomerFieldDefinition::where('organization_id', $customer->organization_id)
            ->active()->orderBy('sort_order')->get();

        return view('customer::show', compact('customer', 'fieldDefs'));
    }

    public function edit(Customer $customer): View
    {
        $this->authorize('update', $customer);

        // Dùng org của CHÍNH customer (không phải TenantContext hiện tại của người sửa) —
        // quan trọng khi super-admin sửa khách hàng thuộc org khác với org đang active.
        $orgId     = $customer->organization_id;
        $customer->load(['tags', 'meta.definition']);
        $sources   = LeadSource::where('is_active', true)->orderBy('sort_order')->get(['id', 'label']);
        $tags      = CustomerTag::where('organization_id', $orgId)->orderBy('name')->get(['id', 'name', 'color']);
        $sizes     = collect(CompanySize::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]);
        $stages    = collect(CustomerLifecycleStage::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]);
        $provinces = Province::where('is_active', true)->orderBy('name')->get(['province_code', 'name']);
        $fieldDefs = CustomerFieldDefinition::where('organization_id', $orgId)
            ->active()->orderBy('sort_order')->get();

        [$organizations, , $orgLocked] = $this->_resolveOrganizations();

        // orgLocked → render sẵn server-side (khớp org cố định của customer).
        // Không orgLocked → để trống, JS cascade load theo org đang chọn trên form
        // (ban đầu là org hiện tại của customer, xem data-selected-value trong blade).
        $assignableEmployees = $orgLocked ? $this->_assignableEmployees($orgId) : collect();

        return view('customer::_form', compact(
            'customer', 'sources', 'tags', 'sizes', 'stages', 'provinces', 'fieldDefs', 'assignableEmployees',
            'organizations', 'orgLocked'
        ));
    }

    public function update(Request $request, Customer $customer, UpdateCustomerAction $action): RedirectResponse
    {
        $this->authorize('update', $customer);

        $data = UpdateCustomerData::validateAndCreate($request->all());

        // Chỉ user không khoá org (super-admin) mới được đổi organization_id của customer.
        $canChangeOrg = auth()->user()->organization_id === null;
        $action->handle($customer, $data, $canChangeOrg);

        return redirect()->route('customer.show', $customer)
            ->with('success', 'Đã cập nhật khách hàng.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);

        DeleteCustomerAction::run($customer);

        return redirect()->route('customer.index')
            ->with('success', 'Đã xóa khách hàng.');
    }
}
