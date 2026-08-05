@extends('layouts.frontend')

@section('title', 'Tính lương hưu BHXH tự nguyện')
@section('meta_description', 'Công cụ ước tính mức đóng, mức bình quân thu nhập và lương hưu hằng tháng khi tham gia Bảo hiểm xã hội tự nguyện — dựa trên Nghị định 159/2025/NĐ-CP, tính toán ngay trên trình duyệt, không lưu thông tin bạn nhập.')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8"
     x-data="pensionCalculator({{ Js::from($referenceData) }})">

    <h1 class="text-2xl sm:text-3xl font-bold text-base-content mb-2">Bảng tính minh hoạ lương hưu BHXH tự nguyện</h1>
    <p class="text-sm text-base-content/60 mb-4">Nhập dòng thời gian đóng góp thực tế của bạn để xem ước tính mức đóng, mức bình quân thu nhập và lương hưu hằng tháng.</p>

    {{-- §10.3 — Disclaimer bắt buộc, thường trực, 2 câu tách riêng --}}
    <div class="alert alert-info items-start mb-6 text-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div class="space-y-1.5">
            <p>Công cụ ước tính mang tính tham khảo dựa trên Nghị định 159/2025/NĐ-CP, tính toán ngay trên trình duyệt của bạn — không gửi hoặc lưu trữ thông tin thu nhập bạn nhập.</p>
            <p class="font-semibold text-warning-content bg-warning/20 rounded px-2 py-1">Đây <u>không phải</u> công cụ của Bảo hiểm xã hội Việt Nam và <u>không thay thế</u> hồ sơ/quyết định hưởng chế độ chính thức — số liệu thực tế do cơ quan Bảo hiểm xã hội Việt Nam xác định khi giải quyết hồ sơ, có thể khác kết quả ước tính ở đây do thay đổi chính sách, hệ số trượt giá, hoặc sai lệch thông tin bạn tự nhập.</p>
        </div>
    </div>

    <template x-if="!currentParameterPeriod">
        <div class="alert alert-error mb-6 text-sm">Chưa có tham số BHXH tự nguyện nào được cấu hình cho thời điểm hiện tại — công cụ chưa thể tính. Vui lòng quay lại sau.</div>
    </template>

    <template x-if="currentParameterPeriod">
    <div>

    <div role="tablist" class="tabs tabs-boxed w-fit mb-5">
        <a role="tab" class="tab" :class="{ 'tab-active': activeTab === 'estimate' }" @click="activeTab = 'estimate'">Ước tính lương hưu</a>
        <a role="tab" class="tab" :class="{ 'tab-active': activeTab === 'optimize' }" @click="activeTab = 'optimize'">Dự báo &amp; Tối ưu mức đóng</a>
    </div>

    {{-- ══════════════════════════════ TAB 1: ƯỚC TÍNH (MVP, §7) ══════════════════════════════ --}}
    <div x-show="activeTab === 'estimate'" x-cloak class="space-y-6">

        {{-- Bước 1 --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body py-4 px-4">
                <h2 class="flex items-center gap-2 font-semibold text-base-content mb-3"><span class="badge badge-primary badge-sm w-5 h-5 p-0 justify-center font-bold">1</span> Thông tin cơ bản</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Giới tính</span></label>
                        <select class="select select-bordered select-sm" x-model="gender">
                            <option value="male">Nam</option>
                            <option value="female">Nữ</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Năm sinh</span></label>
                        <input type="number" class="input input-bordered input-sm" x-model.number="birthYear" :max="currentYear" min="1900" placeholder="VD: 1985">
                    </div>
                </div>

                <label class="label cursor-pointer justify-start gap-2 mt-2 py-1">
                    <input type="checkbox" class="checkbox checkbox-sm" x-model="hasMandatoryHistory">
                    <span class="label-text text-sm">Đã có thời gian đóng BHXH bắt buộc trước đó</span>
                </label>

                <div x-show="hasMandatoryHistory" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-3 pl-6">
                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Tổng số tháng đã đóng bắt buộc</span></label>
                        <input type="number" min="0" class="input input-bordered input-sm" x-model.number="mandatoryMonths">
                    </div>
                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Mức bình quân tiền lương bắt buộc (tự khai, đ/tháng)</span></label>
                        <input type="number" min="0" step="1000" class="input input-bordered input-sm" x-model.number="mandatoryAverageIncome">
                    </div>
                    <label class="label cursor-pointer justify-start gap-2 sm:col-span-2 py-1">
                        <input type="checkbox" class="checkbox checkbox-sm" x-model="hasSevereWorkCapacityReduction">
                        <span class="label-text text-sm">Suy giảm khả năng lao động ≥ 61% (nghỉ hưu sớm, nhánh (b) Điều 11.2.b)</span>
                    </label>
                </div>
                <p class="text-xs text-base-content/40 mt-1">Module này KHÔNG tự tính mức bình quân tiền lương BHXH bắt buộc (Điều 72/73 Luật) — vui lòng tự khai theo hồ sơ/sổ BHXH.</p>
            </div>
        </div>

        {{-- Bước 2 --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body py-4 px-4">
                <div class="flex items-center justify-between flex-wrap gap-2 mb-1">
                    <h2 class="flex items-center gap-2 font-semibold text-base-content"><span class="badge badge-primary badge-sm w-5 h-5 p-0 justify-center font-bold">2</span> Dòng thời gian đóng BHXH tự nguyện</h2>
                    <button type="button" class="btn btn-primary btn-xs" @click="addRow()">+ Thêm giai đoạn</button>
                </div>

                {{-- §10.6 — thanh tiến trình 120 tháng hỗ trợ --}}
                <div class="mb-3">
                    <div class="flex justify-between text-xs text-base-content/60 mb-1">
                        <span>Đã dùng hỗ trợ nhà nước</span>
                        <span><span x-text="supportMonthsUsed"></span>/120 tháng</span>
                    </div>
                    <progress class="progress w-full" :class="isSupportExhausted ? 'progress-warning' : 'progress-primary'" :value="supportMonthsUsed" max="120"></progress>
                    <p class="text-xs text-warning mt-1" x-show="isSupportExhausted" x-cloak>Đã hết thời hạn hỗ trợ nhà nước tối đa 120 tháng (Điều 5.2 Nghị định 159) — các giai đoạn phía sau mốc này tự động tính đủ 22%, không còn phần hỗ trợ.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="table table-sm table-zebra">
                        <thead>
                            <tr>
                                <th>Từ</th>
                                <th>Đến</th>
                                <th>Mức thu nhập chọn đóng (đ/tháng)</th>
                                <th>Nhóm hỗ trợ</th>
                                <th>Mức đóng/tháng</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, idx) in contributionRows" :key="idx">
                                <tr>
                                    <td class="whitespace-nowrap">
                                        <div class="flex gap-1">
                                            <input type="number" min="1" max="12" class="input input-bordered input-xs w-14" x-model.number="row.fromMonth" placeholder="Tháng">
                                            <input type="number" class="input input-bordered input-xs w-20" x-model.number="row.fromYear" placeholder="Năm">
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div class="flex gap-1">
                                            <input type="number" min="1" max="12" class="input input-bordered input-xs w-14" x-model.number="row.toMonth" placeholder="Tháng">
                                            <input type="number" class="input input-bordered input-xs w-20" x-model.number="row.toYear" placeholder="Năm">
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number" step="1000" class="input input-bordered input-xs w-32"
                                               x-model.number="row.income"
                                               :min="currentParameterPeriod.rural_poverty_line"
                                               :max="currentParameterPeriod.reference_level * currentParameterPeriod.ceiling_multiplier">
                                    </td>
                                    <td>
                                        <select class="select select-bordered select-xs w-full min-w-36" x-model="row.supportGroup">
                                            <option value="none">Không thuộc diện hỗ trợ</option>
                                            <template x-for="tier in currentParameterPeriod.support_tiers" :key="tier.group_key">
                                                <option :value="tier.group_key" x-text="supportGroupLabel(tier.group_key)"></option>
                                            </template>
                                        </select>
                                    </td>
                                    <td class="text-right whitespace-nowrap">
                                        <template x-if="monthlyContributionFor(row).missingPeriod">
                                            <span class="text-warning text-xs" x-text="'Chưa có tham số cho giai đoạn này (trước ' + (currentParameterPeriod ? formatMonthYear(currentParameterPeriod.effective_from) : '?') + ')'"></span>
                                        </template>
                                        <template x-if="!monthlyContributionFor(row).missingPeriod">
                                            <div>
                                                <span x-text="formatVnd(monthlyContributionFor(row).net)"></span>
                                                <div class="text-xs text-warning" x-show="monthlyContributionFor(row).exhausted" x-cloak>Đã hết hỗ trợ 120 tháng — đủ 22%</div>
                                            </div>
                                        </template>
                                    </td>
                                    <td><button type="button" class="btn btn-ghost btn-xs text-error" @click="removeRow(row)" x-show="contributionRows.length > 1">Xoá</button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-base-content/40 mt-1">Mức thu nhập hợp lệ: <span x-text="formatVnd(currentParameterPeriod.rural_poverty_line)"></span> – <span x-text="formatVnd(currentParameterPeriod.reference_level * currentParameterPeriod.ceiling_multiplier)"></span> (§6.1). Có thể để trống 1 khoảng thời gian giữa 2 giai đoạn nếu bạn nghỉ đóng.</p>
            </div>
        </div>

        {{-- Bước 3 — §10.5 breakdown --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body py-4 px-4">
                <h2 class="flex items-center gap-2 font-semibold text-base-content mb-3"><span class="badge badge-primary badge-sm w-5 h-5 p-0 justify-center font-bold">3</span> Mức bình quân thu nhập tháng đóng BHXH (Mbq)</h2>
                <div class="overflow-x-auto">
                    <table class="table table-sm table-zebra">
                        <thead>
                            <tr>
                                <th>Giai đoạn</th>
                                <th class="text-right">Số tháng</th>
                                <th>Hệ số trượt giá</th>
                                <th class="text-right">Thu nhập gốc</th>
                                <th class="text-right">Thành tiền (đã điều chỉnh)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="entry in averageIncomeBreakdown()" :key="entry.label">
                                <tr :class="entry.missingCoefficient ? 'bg-warning/10' : ''">
                                    <td x-text="entry.label"></td>
                                    <td class="text-right" x-text="entry.months"></td>
                                    <td>
                                        <template x-if="entry.missingCoefficient">
                                            <span class="badge badge-warning badge-sm">Thiếu hệ số</span>
                                        </template>
                                        <template x-if="!entry.missingCoefficient">
                                            <span x-text="entry.segments.map(s => s.year + ': ' + s.coefficient).join(', ')"></span>
                                        </template>
                                    </td>
                                    <td class="text-right" x-text="formatVnd(entry.rawIncome)"></td>
                                    <td class="text-right" x-text="entry.missingCoefficient ? '—' : formatVnd(entry.adjustedTotal)"></td>
                                </tr>
                            </template>
                            <tr x-show="hasMandatoryHistory" x-cloak class="font-medium">
                                <td>BHXH bắt buộc (tự khai)</td>
                                <td class="text-right" x-text="mandatoryMonths"></td>
                                <td>—</td>
                                <td class="text-right" x-text="formatVnd(mandatoryAverageIncome * mandatoryMonths)"></td>
                                <td class="text-right" x-text="formatVnd(mandatoryAverageIncome * mandatoryMonths)"></td>
                            </tr>
                            <tr class="font-semibold border-t-2 border-base-300">
                                <td>Tổng</td>
                                <td class="text-right" x-text="totalVoluntaryMonths + (hasMandatoryHistory ? mandatoryMonths : 0)"></td>
                                <td></td>
                                <td></td>
                                <td class="text-right" x-text="averageMonthlyIncome === null ? '—' : formatVnd(averageMonthlyIncome) + ' (Mbq)'"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <template x-if="averageMonthlyIncome === null">
                    <p class="text-xs text-warning mt-1">Chưa đủ dữ liệu để tính Mbq — kiểm tra các dòng có nhãn "Thiếu hệ số" ở trên (chưa có hệ số trượt giá cho năm đóng đó trong hệ thống).</p>
                </template>
            </div>
        </div>

        {{-- Bước 4 --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body py-4 px-4">
                <h2 class="flex items-center gap-2 font-semibold text-base-content mb-3"><span class="badge badge-primary badge-sm w-5 h-5 p-0 justify-center font-bold">4</span> Điều kiện hưởng lương hưu</h2>
                <p class="text-sm">Nhánh áp dụng: <span class="font-medium" x-text="pensionEligibility.branchLabel"></span></p>
                <p class="text-sm mt-1">Số năm đóng tích luỹ: <span x-text="(pensionEligibility.monthsAccumulated/12).toFixed(1)"></span> năm / cần <span x-text="pensionEligibility.monthsRequired/12"></span> năm.</p>

                {{-- Lưu ý DaisyUI .alert mặc định display:flex (hàng ngang) — nếu để nhiều thẻ
                     con cấp cao nhất (p/ul/p...) không bọc trong 1 wrapper, chúng sẽ bị xếp
                     THÀNH CÁC CỘT cạnh nhau thay vì xếp chồng dọc (bug đã gặp 2026-08-05, xem
                     git blame). Mọi alert có >1 khối con phải bọc trong 1 <div> duy nhất. --}}
                <template x-if="!pensionEligibility.eligibleByYears">
                    <div class="alert alert-info items-start text-sm mt-2">
                        <div class="space-y-1.5">
                            <p>Còn thiếu <span class="font-semibold" x-text="pensionEligibility.yearsShort"></span> năm <span class="font-semibold" x-text="pensionEligibility.monthsShortRemainder || 0"></span> tháng để đủ điều kiện. Có 3 hướng cân nhắc:</p>
                            <ul class="list-disc pl-4 space-y-0.5">
                                <li><strong>Tiếp tục đóng BHXH tự nguyện</strong> tới khi đủ số năm — xem bảng minh hoạ ước tính ở cuối Bước 5 (dựa trên mức thu nhập bạn giả định, không áp hệ số trượt giá tương lai).</li>
                                <li>Đóng bù 1 lần cho thời gian còn thiếu (Điều 7 Nghị định 159) — chỉ áp dụng nếu đã đủ tuổi nghỉ hưu VÀ số tháng thiếu ≤ 60 tháng.</li>
                                <li>Chờ hưởng trợ cấp hằng tháng khi đủ tuổi (Điều 14 Nghị định 159) — dành cho người không muốn/không đủ điều kiện đóng bù.</li>
                            </ul>
                            <p class="text-xs opacity-80">2 phương án cuối (đóng bù 1 lần / trợ cấp hằng tháng) công cụ <strong>CHƯA tính được số cụ thể</strong> — công thức cần mức trợ cấp hưu trí xã hội hằng tháng (Điều 21 Luật Bảo hiểm xã hội 2024) hiện chưa có nguồn xác minh (spec §14 mục 5), không phải giới hạn kỹ thuật mà là thiếu số liệu pháp luật, nên KHÔNG bịa số.</p>
                        </div>
                    </div>
                </template>
                <template x-if="pensionEligibility.eligibleByYears && !pensionEligibility.ageVerified">
                    <div class="alert alert-info text-sm mt-2">
                        <span>Đã đủ số năm đóng theo nhánh này. Điều kiện tuổi tra theo <span x-text="pensionEligibility.ageRequirementNote"></span> — công cụ CHƯA có bảng tuổi nghỉ hưu đã xác minh nên không thể tự xác nhận bạn đã đủ tuổi hay chưa, vui lòng đối chiếu thêm quy định tuổi nghỉ hưu hiện hành.</span>
                    </div>
                </template>
                <template x-if="pensionEligibility.eligibleByYears && pensionEligibility.ageVerified">
                    <div class="alert text-sm mt-2" :class="pensionEligibility.ageOk ? 'alert-success' : 'alert-warning'">
                        <span><span x-text="pensionEligibility.ageRequirementNote"></span> — <span x-text="pensionEligibility.ageOk ? 'đã đủ tuổi.' : (currentAge !== null ? ('hiện ' + currentAge + ' tuổi, chưa đủ.') : 'vui lòng nhập năm sinh để kiểm tra.')"></span></span>
                    </div>
                </template>
            </div>
        </div>

        {{-- Bước 5 --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body py-4 px-4">
                <h2 class="flex items-center gap-2 font-semibold text-base-content mb-3"><span class="badge badge-primary badge-sm w-5 h-5 p-0 justify-center font-bold">5</span> Ước tính lương hưu hằng tháng</h2>

                <template x-if="estimatedMonthlyPension.value !== null">
                    <p class="text-2xl font-bold text-primary" x-text="formatVnd(estimatedMonthlyPension.value) + ' / tháng'"></p>
                </template>

                <template x-if="estimatedMonthlyPension.reason === 'needs_verified_rate_table'">
                    {{-- §10.4 --}}
                    <div class="alert alert-warning text-sm">Chưa thể ước tính số tiền lương hưu cụ thể — đang chờ xác minh tỷ lệ hưởng theo Luật Bảo hiểm xã hội 2024.</div>
                </template>
                <template x-if="estimatedMonthlyPension.reason === 'missing_mbq'">
                    <div class="alert alert-warning text-sm">Chưa tính được vì Mbq (Bước 3) còn thiếu dữ liệu — bổ sung hệ số trượt giá còn thiếu ở Bước 3.</div>
                </template>
                <template x-if="estimatedMonthlyPension.reason === 'not_eligible_years'">
                    <div class="alert alert-ghost text-sm border border-base-200">Chưa đủ điều kiện năm đóng (Bước 4) nên chưa thể ước tính lương hưu.</div>
                </template>
            </div>
        </div>

        {{-- Bảng minh hoạ "nếu tiếp tục đóng" — chỉ hiển thị khi CHƯA đủ điều kiện năm đóng
             (Bước 4), bổ sung theo yêu cầu: ước tính tương đối lương hưu nếu tiếp tục đóng thêm
             ở 3 mức thu nhập giả định cho tới khi đủ số năm, dùng giá trị hiện tại (không đoán
             hệ số trượt giá tương lai). --}}
        <template x-if="!pensionEligibility.eligibleByYears">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body py-4 px-4">
                <h2 class="font-semibold text-base-content mb-1">Minh hoạ: nếu tiếp tục đóng đến khi đủ điều kiện</h2>
                <p class="text-xs text-base-content/50 mb-3">
                    Giả định 3 mức thu nhập chọn đóng cho các tháng CÒN THIẾU (mặc định tự đồng bộ theo mức gần nhất bạn đã nhập ở Bước 2, có thể sửa tay — 1 khi đã tự sửa thì không tự đồng bộ nữa cho tới khi bấm "Đặt lại theo mức gần nhất").
                    Các tháng đóng thêm áp hệ số trượt giá CỐ ĐỊNH bằng hệ số của năm hiện tại (chưa có hệ số nào được BHXH Việt Nam công bố cho các năm sau này) — chỉ mang tính minh hoạ tương đối, KHÔNG phải dự báo chính xác.
                </p>

                <button type="button" class="btn btn-ghost btn-xs w-fit mb-2" @click="resetProjectionIncomes()">Đặt lại theo mức gần nhất</button>

                <div class="overflow-x-auto">
                    <table class="table table-sm table-zebra">
                        <thead>
                            <tr>
                                <th>Kịch bản</th>
                                <th class="text-right">Mức thu nhập chọn đóng (đ/tháng)</th>
                                <th class="text-right">Mức đóng/tháng ước tính</th>
                                <th class="text-right">Dự kiến đủ điều kiện vào</th>
                                <th class="text-right">Mbq minh hoạ</th>
                                <th class="text-right">Lương hưu minh hoạ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="scenario in [
                                { key: 'lower', label: 'Thấp hơn' },
                                { key: 'current', label: 'Giữ nguyên mức gần nhất' },
                                { key: 'higher', label: 'Cao hơn' },
                            ]" :key="scenario.key">
                                <tr>
                                    <td x-text="scenario.label"></td>
                                    <td class="text-right">
                                        <input type="number" step="1000" class="input input-bordered input-xs w-32 text-right"
                                               x-model.number="projectionIncomes[scenario.key]" @input="projectionIncomesTouched = true">
                                    </td>
                                    <template x-if="!projectPensionFor(projectionIncomes[scenario.key]).blocked">
                                    <td class="text-right" x-text="formatVnd(projectPensionFor(projectionIncomes[scenario.key]).monthlyContribution.net)"></td>
                                    </template>
                                    <template x-if="!projectPensionFor(projectionIncomes[scenario.key]).blocked">
                                    <td class="text-right" x-text="projectPensionFor(projectionIncomes[scenario.key]).eligibleAtLabel"></td>
                                    </template>
                                    <template x-if="!projectPensionFor(projectionIncomes[scenario.key]).blocked">
                                    <td class="text-right" x-text="formatVnd(projectPensionFor(projectionIncomes[scenario.key]).projectedMbq)"></td>
                                    </template>
                                    <template x-if="!projectPensionFor(projectionIncomes[scenario.key]).blocked">
                                        <td class="text-right">
                                            <template x-if="!projectPensionFor(projectionIncomes[scenario.key]).needsVerifiedRateTable">
                                                <span class="font-medium text-primary" x-text="formatVnd(projectPensionFor(projectionIncomes[scenario.key]).projectedPension)"></span>
                                            </template>
                                            <template x-if="projectPensionFor(projectionIncomes[scenario.key]).needsVerifiedRateTable">
                                                <span class="text-warning text-xs">Chưa xác minh tỷ lệ hưởng</span>
                                            </template>
                                        </td>
                                    </template>
                                    <template x-if="projectPensionFor(projectionIncomes[scenario.key]).blocked">
                                        <td colspan="4" class="text-warning text-xs">Chưa đủ dữ liệu để minh hoạ (thiếu hệ số trượt giá ở Bước 3, hoặc chưa có tham số hiện hành).</td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-base-content/40 mt-2">"Dự kiến đủ điều kiện vào" tính từ số tháng còn thiếu theo nhánh điều kiện đang áp dụng (Bước 4) — KHÔNG tính lại điều kiện tuổi (xem lại phần cảnh báo tuổi ở Bước 4 cho mốc thời gian này).</p>
            </div>
        </div>
        </template>

        {{-- Bảng minh hoạ CHI TIẾT THEO NĂM cho kịch bản "giữ nguyên mức đóng gần nhất" — bổ
             sung theo yêu cầu người dùng 2026-08-05: xem từng năm còn thiếu sẽ đóng bao nhiêu
             tháng, luỹ kế ra sao, và lương hưu ước tính khi đủ điều kiện. Hệ số trượt giá cho
             các năm chưa tới lấy CỐ ĐỊNH bằng hệ số của năm hiện tại (xem currentYearCoefficient)
             — KHÔNG phải hệ số đã được BHXH Việt Nam công bố cho các năm đó (chưa tồn tại). --}}
        <template x-if="!pensionEligibility.eligibleByYears && !projectPensionFor(projectionIncomes.current).blocked && projectPensionFor(projectionIncomes.current).yearlyRows.length">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body py-4 px-4">
                <h2 class="font-semibold text-base-content mb-1">Bảng minh hoạ chi tiết theo năm — giữ nguyên mức đóng hiện tại</h2>
                <p class="text-xs text-base-content/50 mb-3">
                    Giả định tiếp tục đóng đúng <span x-text="formatVnd(projectionIncomes.current)"></span>/tháng cho các tháng còn thiếu, hệ số trượt giá của các năm chưa tới lấy CỐ ĐỊNH bằng hệ số năm hiện tại (<span x-text="currentYear"></span>: hệ số <span x-text="Number(currentYearCoefficient).toFixed(2)"></span>) — không phải hệ số chính thức cho các năm đó (chưa được BHXH Việt Nam công bố), chỉ minh hoạ tương đối.
                </p>

                {{-- Bảng cuộn riêng trong khung cao tối đa — tránh kéo dài cả trang khi số năm
                     còn thiếu lớn (VD 13-15 năm ra 13-15 dòng); phần tổng kết (Mbq/lương hưu) để
                     RIÊNG ngoài vùng cuộn (dưới đây) để luôn nhìn thấy, không bị cuộn mất. --}}
                <div class="overflow-x-auto overflow-y-auto max-h-72 border border-base-200 rounded-lg">
                    <table class="table table-sm table-zebra table-pin-rows">
                        <thead>
                            <tr>
                                <th>Năm</th>
                                <th class="text-right">Số tháng đóng thêm trong năm</th>
                                <th class="text-right">Luỹ kế số tháng đóng</th>
                                <th class="text-right">Hệ số áp dụng</th>
                                <th class="text-right">Thành tiền trong năm (đã điều chỉnh)</th>
                                <th class="text-right">Luỹ kế thu nhập điều chỉnh</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="row in projectPensionFor(projectionIncomes.current).yearlyRows" :key="row.year">
                                <tr>
                                    <td x-text="row.year"></td>
                                    <td class="text-right" x-text="row.months"></td>
                                    <td class="text-right" x-text="row.cumulativeMonths"></td>
                                    <td class="text-right" x-text="Number(row.coefficient).toFixed(2)"></td>
                                    <td class="text-right" x-text="formatVnd(row.amount)"></td>
                                    <td class="text-right" x-text="formatVnd(row.cumulativeAdjusted)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="stats stats-vertical sm:stats-horizontal shadow-sm border border-base-200 w-full mt-3">
                    <div class="stat py-2 px-4">
                        <div class="stat-title text-xs">Mbq dự kiến khi đủ điều kiện (<span x-text="projectPensionFor(projectionIncomes.current).eligibleAtLabel"></span>)</div>
                        <div class="stat-value text-lg" x-text="formatVnd(projectPensionFor(projectionIncomes.current).projectedMbq)"></div>
                    </div>
                    <div class="stat py-2 px-4">
                        <div class="stat-title text-xs">Lương hưu ước tính hằng tháng sau khi đủ điều kiện</div>
                        <template x-if="projectPensionFor(projectionIncomes.current).needsVerifiedRateTable">
                            <div class="stat-value text-warning text-sm">Chưa xác minh tỷ lệ hưởng</div>
                        </template>
                        <template x-if="!projectPensionFor(projectionIncomes.current).needsVerifiedRateTable">
                            <div class="stat-value text-primary text-lg" x-text="formatVnd(projectPensionFor(projectionIncomes.current).projectedPension)"></div>
                        </template>
                    </div>
                </div>

                <p class="text-xs text-base-content/40 mt-2">Muốn xem theo mức đóng khác (thấp hơn/cao hơn) — sửa ô "Giữ nguyên mức gần nhất" ở bảng minh hoạ phía trên, bảng chi tiết này sẽ tự cập nhật theo.</p>
            </div>
        </div>
        </template>
    </div>

    {{-- ══════════════════════════════ TAB 2: PHASE 1.5 — DỰ BÁO & TỐI ƯU (§15) ══════════════════════════════ --}}
    <div x-show="activeTab === 'optimize'" x-cloak class="space-y-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body py-4 px-4">
                <h2 class="font-semibold text-base-content mb-1">Dự báo &amp; Tối ưu mức đóng</h2>
                <p class="text-xs text-base-content/50 mb-3">Nhập mục tiêu lương hưu mong muốn, công cụ tự tìm mức thu nhập chọn đóng (TN) tối thiểu cần thiết. Giả định đơn giản hoá: Mbq ≈ TN không đổi suốt số năm đóng (§15.1) — kết quả mang tính minh hoạ, không thay thế Bước 1-5.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Mục tiêu lương hưu hằng tháng (đ)</span></label>
                        <input type="number" step="10000" class="input input-bordered input-sm" x-model.number="optimizer.targetPension">
                    </div>
                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Số năm dự kiến đóng</span></label>
                        <input type="number" min="1" class="input input-bordered input-sm" x-model.number="optimizer.years">
                    </div>
                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Nhóm hỗ trợ</span></label>
                        <select class="select select-bordered select-sm" x-model="optimizer.supportGroup">
                            <option value="none">Không thuộc diện hỗ trợ</option>
                            <template x-for="tier in currentParameterPeriod.support_tiers" :key="tier.group_key">
                                <option :value="tier.group_key" x-text="supportGroupLabel(tier.group_key)"></option>
                            </template>
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Số tháng ĐÃ nhận hỗ trợ trước đó</span></label>
                        <input type="number" min="0" max="120" class="input input-bordered input-sm" x-model.number="optimizer.priorSupportMonths">
                    </div>
                    <label class="label cursor-pointer justify-start gap-2 sm:col-span-2 py-1">
                        <input type="checkbox" class="checkbox checkbox-sm" x-model="optimizer.hasMandatory20Years">
                        <span class="label-text text-sm">Có ≥ 20 năm BHXH bắt buộc (áp sàn mức tham chiếu, §6.8)</span>
                    </label>
                </div>

                <button type="button" class="btn btn-primary btn-sm w-fit mt-3" @click="runOptimizer()">Tính TN tối thiểu</button>

                <div class="mt-4" x-show="optimizer.result" x-cloak>
                    <template x-if="optimizer.result?.needsVerifiedRateTable">
                        <div class="alert alert-warning text-sm">Chưa thể tính — đang chờ xác minh tỷ lệ hưởng lương hưu theo Luật Bảo hiểm xã hội 2024 (§14 mục 1), giống lý do ở Bước 5 tab Ước tính.</div>
                    </template>
                    <template x-if="optimizer.result?.achievable === false">
                        <div class="alert alert-warning text-sm">Mục tiêu vượt quá khả năng tối đa — kể cả đóng ở mức trần cũng chỉ đạt tối đa <span x-text="formatVnd(optimizer.result.maxPossiblePension)"></span>/tháng.</div>
                    </template>
                    <template x-if="optimizer.result?.achievable === true">
                        <div class="space-y-1 text-sm">
                            <p>Mức thu nhập chọn đóng tối thiểu cần: <span class="font-bold text-primary" x-text="formatVnd(optimizer.result.requiredIncome)"></span></p>
                            <p>Mức đóng ròng hằng tháng tương ứng: <span x-text="formatVnd(optimizer.result.monthlyContribution.net)"></span></p>
                            <p>Tổng chi phí đóng ước tính (danh nghĩa, không chiết khấu/lạm phát): <span x-text="formatVnd(optimizer.result.totalCostEstimate)"></span></p>
                            <p class="text-warning" x-show="optimizer.result.willExhaustSupport" x-cloak>Trong quá trình đóng sẽ chạm mốc hết hỗ trợ nhà nước 120 tháng — <span x-text="optimizer.result.monthsWithoutSupport"></span> tháng cuối tính đủ 22%, không còn hỗ trợ.</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Bảng minh hoạ theo tuổi bắt đầu tham gia — nhiều mục tiêu lương hưu, so sánh Nam/Nữ
             cùng lúc (bổ sung theo yêu cầu, cùng khuôn ví dụ "NAM 37T, NỮ 37T" của đại lý BHXH —
             xem §15.5b). Tái dùng ĐÚNG findMinimumIncomeForTarget đã kiểm chứng ở trên cho mỗi
             giới, KHÔNG tự chế tỷ lệ hưởng riêng cho bảng này — khi pension_rate_brackets còn
             trống, cột kết quả hiện "chưa xác minh" giống mọi nơi khác trong công cụ. --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body py-4 px-4">
                <h2 class="font-semibold text-base-content mb-1">Bảng minh hoạ theo tuổi bắt đầu tham gia</h2>
                <p class="text-xs text-base-content/50 mb-3">
                    Ví dụ: "Người 37 tuổi muốn nhận lương hưu 10 triệu/tháng thì cần đóng mức nào?" — nhập tuổi bắt đầu + số năm dự kiến đóng của từng giới (công cụ KHÔNG tự suy ra tuổi nghỉ hưu vì lộ trình tuổi nghỉ hưu theo Điều 64/65 Luật Bảo hiểm xã hội 2024 chưa có trong dữ liệu đã xác minh, §14 mục 4 — bạn tự nhập số năm dự kiến theo tuổi nghỉ hưu bạn biết), thêm nhiều dòng mục tiêu lương hưu để so sánh.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-3">
                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Tuổi bắt đầu tham gia</span></label>
                        <input type="number" min="16" class="input input-bordered input-sm" x-model.number="illustration.startAge">
                    </div>
                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Số năm dự kiến đóng — Nữ</span></label>
                        <input type="number" min="1" class="input input-bordered input-sm" x-model.number="illustration.yearsFemale">
                    </div>
                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Số năm dự kiến đóng — Nam</span></label>
                        <input type="number" min="1" class="input input-bordered input-sm" x-model.number="illustration.yearsMale">
                    </div>
                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Nhóm hỗ trợ</span></label>
                        <select class="select select-bordered select-sm" x-model="illustration.supportGroup">
                            <option value="none">Không thuộc diện hỗ trợ</option>
                            <template x-for="tier in currentParameterPeriod.support_tiers" :key="tier.group_key">
                                <option :value="tier.group_key" x-text="supportGroupLabel(tier.group_key)"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-2 mb-2">
                    <button type="button" class="btn btn-ghost btn-xs" @click="illustration.targets.push(illustration.targets.length ? illustration.targets[illustration.targets.length - 1] + 500000 : 10000000)">+ Thêm mục tiêu lương hưu</button>
                </div>

                <div class="overflow-x-auto">
                    <table class="table table-sm table-zebra">
                        <thead>
                            <tr>
                                <th rowspan="2" class="align-bottom">Mục tiêu lương hưu/tháng</th>
                                <th colspan="3" class="text-center text-error">Nữ (<span x-text="illustration.yearsFemale"></span> năm)</th>
                                <th colspan="3" class="text-center text-info">Nam (<span x-text="illustration.yearsMale"></span> năm)</th>
                                <th rowspan="2"></th>
                            </tr>
                            <tr>
                                <th class="text-right">Mức TN cần</th>
                                <th class="text-right">Phí/tháng</th>
                                <th class="text-right">Phí/năm</th>
                                <th class="text-right">Mức TN cần</th>
                                <th class="text-right">Phí/tháng</th>
                                <th class="text-right">Phí/năm</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(target, idx) in illustration.targets" :key="idx">
                                <tr>
                                    <td>
                                        <input type="number" step="100000" class="input input-bordered input-xs w-32" x-model.number="illustration.targets[idx]">
                                    </td>
                                    <template x-if="illustrationResultFor(target, illustration.yearsFemale, 'female').needsVerifiedRateTable">
                                        <td colspan="3" class="text-warning text-xs">Chưa xác minh tỷ lệ hưởng (§14 mục 1)</td>
                                    </template>
                                    <template x-if="!illustrationResultFor(target, illustration.yearsFemale, 'female').needsVerifiedRateTable && illustrationResultFor(target, illustration.yearsFemale, 'female').achievable">
                                        <td class="text-right" x-text="formatVnd(illustrationResultFor(target, illustration.yearsFemale, 'female').requiredIncome)"></td>
                                    </template>
                                    <template x-if="!illustrationResultFor(target, illustration.yearsFemale, 'female').needsVerifiedRateTable && illustrationResultFor(target, illustration.yearsFemale, 'female').achievable">
                                        <td class="text-right" x-text="formatVnd(illustrationResultFor(target, illustration.yearsFemale, 'female').monthlyContribution.net)"></td>
                                    </template>
                                    <template x-if="!illustrationResultFor(target, illustration.yearsFemale, 'female').needsVerifiedRateTable && illustrationResultFor(target, illustration.yearsFemale, 'female').achievable">
                                        <td class="text-right" x-text="formatVnd(illustrationResultFor(target, illustration.yearsFemale, 'female').monthlyContribution.net * 12)"></td>
                                    </template>
                                    <template x-if="!illustrationResultFor(target, illustration.yearsFemale, 'female').needsVerifiedRateTable && illustrationResultFor(target, illustration.yearsFemale, 'female').achievable === false">
                                        <td colspan="3" class="text-warning text-xs">Vượt mức trần đóng tối đa</td>
                                    </template>

                                    <template x-if="illustrationResultFor(target, illustration.yearsMale, 'male').needsVerifiedRateTable">
                                        <td colspan="3" class="text-warning text-xs">Chưa xác minh tỷ lệ hưởng (§14 mục 1)</td>
                                    </template>
                                    <template x-if="!illustrationResultFor(target, illustration.yearsMale, 'male').needsVerifiedRateTable && illustrationResultFor(target, illustration.yearsMale, 'male').achievable">
                                        <td class="text-right" x-text="formatVnd(illustrationResultFor(target, illustration.yearsMale, 'male').requiredIncome)"></td>
                                    </template>
                                    <template x-if="!illustrationResultFor(target, illustration.yearsMale, 'male').needsVerifiedRateTable && illustrationResultFor(target, illustration.yearsMale, 'male').achievable">
                                        <td class="text-right" x-text="formatVnd(illustrationResultFor(target, illustration.yearsMale, 'male').monthlyContribution.net)"></td>
                                    </template>
                                    <template x-if="!illustrationResultFor(target, illustration.yearsMale, 'male').needsVerifiedRateTable && illustrationResultFor(target, illustration.yearsMale, 'male').achievable">
                                        <td class="text-right" x-text="formatVnd(illustrationResultFor(target, illustration.yearsMale, 'male').monthlyContribution.net * 12)"></td>
                                    </template>
                                    <template x-if="!illustrationResultFor(target, illustration.yearsMale, 'male').needsVerifiedRateTable && illustrationResultFor(target, illustration.yearsMale, 'male').achievable === false">
                                        <td colspan="3" class="text-warning text-xs">Vượt mức trần đóng tối đa</td>
                                    </template>

                                    <td><button type="button" class="btn btn-ghost btn-xs text-error" @click="illustration.targets.splice(idx, 1)" x-show="illustration.targets.length > 1">Xoá</button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-base-content/40 mt-2">Giả định Mbq ≈ mức thu nhập chọn đóng, không đổi suốt số năm (§15.1) — chỉ mang tính minh hoạ, không thay thế Bước 1-5. "Phí/năm" = "Phí/tháng" × 12, chưa tính chiết khấu/lạm phát.</p>
            </div>
        </div>
    </div>

    </div>
    </template>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    /**
     * spec/bhxh/PensionCalculator_Technical_Specification.md §7-§10, §15 — toàn bộ phép tính
     * chạy TẠI ĐÂY (client), server chỉ cấp `referenceData` 1 lần lúc tải trang (§8, §10.2).
     * KHÔNG gửi bất kỳ giá trị người dùng nhập nào lên server.
     */
    Alpine.data('pensionCalculator', (referenceData) => ({
        referenceData,
        currentYear: new Date().getFullYear(),
        currentMonth: new Date().getMonth() + 1,

        activeTab: 'estimate',

        gender: 'male',
        birthYear: null,
        hasMandatoryHistory: false,
        mandatoryMonths: 0,
        mandatoryAverageIncome: 0,
        hasSevereWorkCapacityReduction: false,
        contributionRows: [],

        optimizer: {
            targetPension: 3000000,
            years: 20,
            supportGroup: 'other',
            priorSupportMonths: 0,
            hasMandatory20Years: false,
            result: null,
        },

        // Bảng minh hoạ "nếu tiếp tục đóng đến khi đủ điều kiện" (mở rộng theo yêu cầu người
        // dùng) — 3 mức thu nhập giả định (thấp hơn/giữ nguyên/cao hơn mức gần nhất đã nhập),
        // hệ số trượt giá tương lai lấy hệ số năm hiện tại (currentYearCoefficient), chỉ mang
        // tính minh hoạ tương đối.
        projectionIncomes: { lower: 1500000, current: 1500000, higher: 1500000 },

        // BUG phát hiện 2026-08-05: projectionIncomes chỉ tính 1 lần lúc init() từ dòng Bước 2
        // lúc đó — nếu người dùng SỬA income ở Bước 2 sau đó mà không bấm nút "Đặt lại theo mức
        // gần nhất", projectionIncomes.current bị CŨ (VD vẫn = chuẩn nghèo mặc định), làm loãng
        // Mbq của bảng minh hoạ xuống sai lệch rất nhiều. Cờ này theo dõi người dùng đã tự tay
        // sửa projectionIncomes hay chưa — CHỈ auto re-sync (xem $watch trong init()) khi CHƯA
        // từng tự sửa, để vẫn tôn trọng "có thể sửa tay" như thiết kế gốc.
        projectionIncomesTouched: false,

        // Bảng minh hoạ theo tuổi bắt đầu tham gia (khuôn ví dụ đại lý BHXH "NAM 37T, NỮ 37T") —
        // nhiều mục tiêu lương hưu, so sánh Nam/Nữ. Số năm đóng NHẬP TAY (không tự suy từ tuổi
        // nghỉ hưu vì Điều 64/65 chưa xác minh, §14 mục 4).
        illustration: {
            startAge: 37,
            yearsFemale: 20,
            yearsMale: 25,
            supportGroup: 'other',
            targets: [10000000],
        },

        init() {
            if (this.contributionRows.length === 0) this.addRow();
            this.resetProjectionIncomes();

            // Auto re-sync projectionIncomes mỗi khi Bước 2 đổi (thêm/xoá/sửa dòng), CHỪNG NÀO
            // người dùng chưa tự tay sửa projectionIncomes — sửa BUG stale value nêu trên. Dùng
            // getter `contributionRowsSignature` (JSON.stringify) làm khoá theo dõi vì $watch chỉ
            // bắt được thay đổi REFERENCE, còn sửa 1 field bên trong 1 phần tử mảng không tự đổi
            // reference của mảng.
            this.$watch('contributionRowsSignature', () => {
                if (!this.projectionIncomesTouched) this.resetProjectionIncomes();
            });
        },

        get contributionRowsSignature() {
            return JSON.stringify(this.contributionRows);
        },

        // ── Helpers ──────────────────────────────────────────────────
        formatVnd(value) {
            if (value === null || value === undefined || Number.isNaN(value)) return '—';
            return Math.round(value).toLocaleString('vi-VN') + ' đ';
        },

        formatMonthYear(isoDate) {
            const [y, m] = isoDate.split('-');
            return `${m}/${y}`;
        },

        // month tuyệt đối (offset tính từ tháng hiện tại, offset=1 → tháng kế tiếp) → {year, month}
        // — tách riêng khỏi monthsFromNowLabel để tái dùng cho bảng minh hoạ theo năm (yearlyRows
        // trong projectPensionFor, yêu cầu người dùng 2026-08-05).
        monthYearForOffset(monthsAhead) {
            const absoluteIndex = this.currentYear * 12 + this.currentMonth + monthsAhead;
            const year = Math.floor((absoluteIndex - 1) / 12);
            const month = ((absoluteIndex - 1) % 12) + 1;
            return { year, month };
        },

        // "dự kiến đủ điều kiện vào tháng nào" ở bảng minh hoạ (§ mở rộng — xem projectPensionFor).
        monthsFromNowLabel(monthsAhead) {
            if (monthsAhead <= 0) return 'Ngay bây giờ';
            const { year, month } = this.monthYearForOffset(monthsAhead);
            return `${String(month).padStart(2, '0')}/${year}`;
        },

        supportGroupLabel(key) {
            return {
                poor_household: 'Hộ nghèo / xã đảo, đặc khu',
                near_poor_household: 'Hộ cận nghèo',
                ethnic_minority: 'Dân tộc thiểu số',
                other: 'Người tham gia khác',
            }[key] ?? key;
        },

        periodFor(year, month) {
            const dateVal = year * 12 + month;
            const candidates = (this.referenceData.parameter_periods || []).filter((p) => {
                const [py, pm] = p.effective_from.split('-').map(Number);
                return (py * 12 + pm) <= dateVal;
            });
            return candidates.length ? candidates[candidates.length - 1] : null;
        },

        get currentParameterPeriod() {
            return this.periodFor(this.currentYear, this.currentMonth);
        },

        get currentAge() {
            if (!this.birthYear) return null;
            return this.currentYear - Number(this.birthYear);
        },

        coefficientFor(settlementYear, contributionYear) {
            return (this.referenceData.price_index_coefficients || [])
                .find((c) => c.settlement_year === settlementYear && c.contribution_year === contributionYear) ?? null;
        },

        // Hệ số trượt giá của NĂM HIỆN TẠI (settlement_year = contribution_year = năm hiện tại)
        // — theo yêu cầu người dùng 2026-08-05: dùng hệ số này CỐ ĐỊNH cho mọi tháng đóng thêm
        // ở các năm CHƯA TỚI trong bảng minh hoạ (projectPensionFor), thay vì bỏ qua điều chỉnh
        // (trước đây coi hệ số tương lai = 1 do chưa có hệ số công bố cho năm chưa tới — về mặt
        // số học 2 cách cho CÙNG kết quả với dữ liệu đã seed, vì hệ số của năm settlement trùng
        // năm đóng luôn = 1.00 theo quy định, nhưng viết tường minh để đúng ý nghĩa nghiệp vụ và
        // vẫn đúng nếu sau này hệ số năm hiện tại được công bố khác 1.00).
        get currentYearCoefficient() {
            const coef = this.coefficientFor(this.currentYear, this.currentYear);
            return coef ? coef.coefficient : 1;
        },

        // ── Bước 2 — dòng thời gian đóng góp ────────────────────────
        addRow() {
            const period = this.currentParameterPeriod;
            this.contributionRows.push({
                fromYear: this.currentYear,
                fromMonth: 1,
                toYear: this.currentYear,
                toMonth: 12,
                income: period ? Number(period.rural_poverty_line) : 1500000,
                supportGroup: 'other',
            });
        },

        removeRow(row) {
            this.contributionRows = this.contributionRows.filter((r) => r !== row);
        },

        rowMonths(row) {
            if (!row.fromYear || !row.fromMonth || !row.toYear || !row.toMonth) return 0;
            const start = row.fromYear * 12 + row.fromMonth;
            const end = row.toYear * 12 + row.toMonth;
            return Math.max(0, end - start + 1);
        },

        rowStart(row) {
            return row.fromYear * 12 + row.fromMonth;
        },

        rowLabel(row) {
            const pad = (n) => String(n).padStart(2, '0');
            return `${pad(row.fromMonth)}/${row.fromYear} – ${pad(row.toMonth)}/${row.toYear}`;
        },

        // sắp theo THỜI GIAN, không phải thứ tự nhập (§7 Bước 2, §10.1 supportMonthsUsed)
        sortedRows() {
            return [...this.contributionRows]
                .filter((r) => this.rowMonths(r) > 0)
                .sort((a, b) => this.rowStart(a) - this.rowStart(b));
        },

        // §5.2 Nghị định 159 — tối đa 120 tháng hỗ trợ theo thời gian THỰC ĐÓNG (§11 edge case 1)
        supportMonthsUsedBeforeRow(row) {
            let total = 0;
            for (const r of this.sortedRows()) {
                if (r === row) break;
                if (r.supportGroup !== 'none') total += this.rowMonths(r);
            }
            return total;
        },

        get supportMonthsUsed() {
            const total = this.sortedRows().reduce((sum, r) => sum + (r.supportGroup !== 'none' ? this.rowMonths(r) : 0), 0);
            return Math.min(120, total);
        },

        get supportMonthsRemaining() {
            return Math.max(0, 120 - this.supportMonthsUsed);
        },

        get isSupportExhausted() {
            return this.supportMonthsUsed >= 120;
        },

        // §6.1 — mức đóng hằng tháng: MĐ = 22% × TN − HTr, HTr = k × (22% × CN)
        monthlyContributionFor(row) {
            const period = this.periodFor(row.fromYear, row.fromMonth);
            if (!period) return { gross: 0, support: 0, net: 0, exhausted: false, missingPeriod: true };

            const grossRate = period.contribution_rate_percent / 100;
            const gross = (row.income || 0) * grossRate;

            if (row.supportGroup === 'none') {
                return { gross, support: 0, net: gross, exhausted: false, missingPeriod: false };
            }

            const usedBefore = this.supportMonthsUsedBeforeRow(row);
            if (usedBefore >= 120) {
                return { gross, support: 0, net: gross, exhausted: true, missingPeriod: false };
            }

            const tier = (period.support_tiers || []).find((t) => t.group_key === row.supportGroup);
            const supportPercent = tier ? tier.support_percent / 100 : 0;
            const support = supportPercent * (grossRate * period.rural_poverty_line);

            return { gross, support, net: gross - support, exhausted: false, missingPeriod: false };
        },

        // §6.6 — điều chỉnh thu nhập theo hệ số trượt giá, tra theo TỪNG NĂM đã đóng trong dòng
        // (settlement_year = năm hiện tại người dùng đang xem trang). §11 edge case 4 — thiếu hệ
        // số KHÔNG mặc định = 1, phải cảnh báo.
        adjustedIncomeFor(row) {
            const months = this.rowMonths(row);
            if (months <= 0) return { months: 0, rawIncome: 0, adjustedTotal: 0, missingCoefficient: false, segments: [] };

            let adjustedTotal = 0;
            let missingCoefficient = false;
            const segments = [];

            for (let y = row.fromYear; y <= row.toYear; y++) {
                const segStartMonth = y === row.fromYear ? row.fromMonth : 1;
                const segEndMonth = y === row.toYear ? row.toMonth : 12;
                const segMonths = segEndMonth - segStartMonth + 1;
                const coef = this.coefficientFor(this.currentYear, y);

                if (!coef) {
                    missingCoefficient = true;
                    segments.push({ year: y, months: segMonths, coefficient: null });
                    continue;
                }

                const segAmount = segMonths * (row.income || 0) * coef.coefficient;
                adjustedTotal += segAmount;
                segments.push({ year: y, months: segMonths, coefficient: coef.coefficient, amount: segAmount });
            }

            return {
                months,
                rawIncome: months * (row.income || 0),
                adjustedTotal: missingCoefficient ? null : adjustedTotal,
                missingCoefficient,
                segments,
            };
        },

        // §10.5 — breakdown 1 dòng/giai đoạn đã nhập
        averageIncomeBreakdown() {
            return this.sortedRows().map((row) => ({
                row,
                label: this.rowLabel(row),
                ...this.adjustedIncomeFor(row),
            }));
        },

        get totalVoluntaryMonths() {
            return this.sortedRows().reduce((sum, r) => sum + this.rowMonths(r), 0);
        },

        // §6.7 — Mbq mixed hoặc thuần tự nguyện (rút gọn)
        get averageMonthlyIncome() {
            const breakdown = this.averageIncomeBreakdown();
            if (!breakdown.length || breakdown.some((b) => b.missingCoefficient)) return null;

            const totalVoluntaryMonths = this.totalVoluntaryMonths;
            const totalAdjustedVoluntary = breakdown.reduce((sum, b) => sum + b.adjustedTotal, 0);

            if (this.hasMandatoryHistory && this.mandatoryMonths > 0) {
                const totalMonths = totalVoluntaryMonths + Number(this.mandatoryMonths);
                if (totalMonths <= 0) return null;
                const totalAmount = Number(this.mandatoryAverageIncome) * Number(this.mandatoryMonths) + totalAdjustedVoluntary;
                return totalAmount / totalMonths;
            }

            if (totalVoluntaryMonths <= 0) return null;
            return totalAdjustedVoluntary / totalVoluntaryMonths;
        },

        // §6.8 — 4 nhánh điều kiện hưởng lương hưu. Nhánh (a)/(b)/(d) cần bảng tuổi theo Điều
        // 64/65 Luật Bảo hiểm xã hội 2024 — CHƯA có trong dữ liệu đã xác minh (§14 mục 4/4b),
        // nên chỉ xác nhận được điều kiện SỐ NĂM đóng, không tự khẳng định đã đủ tuổi hay chưa.
        buildEligibilityResult(branch, branchLabel, required, accumulated, ageVerified, ageNote, ageOk = null) {
            const short = Math.max(0, required - accumulated);
            return {
                branch,
                branchLabel,
                monthsAccumulated: accumulated,
                monthsRequired: required,
                yearsShort: Math.floor(short / 12),
                monthsShortRemainder: short % 12,
                ageVerified,
                ageRequirementNote: ageNote,
                ageOk,
                eligibleByYears: accumulated >= required,
                eligibleOverall: accumulated >= required ? (ageVerified ? ageOk : null) : false,
            };
        },

        get pensionEligibility() {
            const totalVoluntaryMonths = this.totalVoluntaryMonths;
            const totalMandatoryMonths = this.hasMandatoryHistory ? Number(this.mandatoryMonths || 0) : 0;
            const totalCombinedMonths = totalVoluntaryMonths + totalMandatoryMonths;
            const rows = this.sortedRows();
            const earliestStart = rows.length ? this.rowStart(rows[0]) : null;
            const startsFrom2021 = earliestStart !== null && earliestStart >= (2021 * 12 + 1);

            if (this.hasMandatoryHistory && totalMandatoryMonths >= 15 * 12) {
                return this.buildEligibilityResult(
                    'a', 'Mixed, có BHXH bắt buộc ≥15 năm (Điều 11.2.a)',
                    15 * 12, totalCombinedMonths, false,
                    'Điều 64 Luật Bảo hiểm xã hội 2024 (lộ trình tuổi nghỉ hưu chung) — chưa có trong dữ liệu đã xác minh của công cụ này.'
                );
            }

            if (this.hasMandatoryHistory && this.hasSevereWorkCapacityReduction && totalMandatoryMonths >= 20 * 12) {
                return this.buildEligibilityResult(
                    'b', 'Mixed, suy giảm khả năng lao động ≥61%, ≥20 năm bắt buộc (Điều 11.2.b)',
                    20 * 12, totalCombinedMonths, false,
                    'Điều 65 Luật Bảo hiểm xã hội 2024 (nghỉ hưu sớm do suy giảm lao động) — chưa có trong dữ liệu đã xác minh của công cụ này.'
                );
            }

            if (!this.hasMandatoryHistory && earliestStart !== null && !startsFrom2021 && totalVoluntaryMonths >= 20 * 12) {
                const requiredAge = this.gender === 'female' ? 55 : 60;
                const ageOk = this.currentAge !== null ? this.currentAge >= requiredAge : null;
                return this.buildEligibilityResult(
                    'c', 'Thuần tự nguyện, tham gia trước 01/01/2021 (Điều 11.2.c/Điều 13.1)',
                    20 * 12, totalVoluntaryMonths, true,
                    `Đủ ${requiredAge} tuổi (${this.gender === 'female' ? 'nữ' : 'nam'})`, ageOk
                );
            }

            // nhánh (d) — catch-all: tham gia từ 01/01/2021 trở đi, hoặc mixed không rơi vào (a)/(b)/(c)
            return this.buildEligibilityResult(
                'd', 'Tham gia từ 01/01/2021 trở đi, hoặc mixed không rơi vào (a)/(b)/(c) — Điều 98 Luật (§6.8, suy luận cấu trúc, chưa xác minh trực tiếp)',
                15 * 12, totalCombinedMonths, false,
                'Điều 64 Luật Bảo hiểm xã hội 2024 (lộ trình tuổi nghỉ hưu chung) — chưa có trong dữ liệu đã xác minh của công cụ này.'
            );
        },

        // §6.9 — tra bảng bậc thang, KHÔNG nội suy giữa 2 mốc nếu thiếu dữ liệu (§15.4 điểm 3)
        pensionRateFor(gender, years) {
            const brackets = (this.referenceData.rate_brackets || []).filter((b) => b.gender === gender);
            if (!brackets.length) return null;

            const eligible = brackets
                .filter((b) => b.min_years_for_base_rate <= years)
                .sort((a, b) => b.min_years_for_base_rate - a.min_years_for_base_rate);

            if (!eligible.length) return null;

            const bracket = eligible[0];
            const rate = bracket.base_rate_percent + bracket.increment_percent_per_year * (years - bracket.min_years_for_base_rate);
            return Math.min(rate, bracket.max_rate_percent);
        },

        // §6.9 + sàn bảo vệ §6.8 (đoạn 2, Điều 11.3)
        get estimatedMonthlyPension() {
            const mbq = this.averageMonthlyIncome;
            if (mbq === null) return { value: null, reason: 'missing_mbq' };

            const elig = this.pensionEligibility;
            if (!elig.eligibleByYears) return { value: null, reason: 'not_eligible_years' };

            const yearsForRate = Math.floor(elig.monthsAccumulated / 12);
            const rate = this.pensionRateFor(this.gender, yearsForRate);
            if (rate === null) return { value: null, reason: 'needs_verified_rate_table' };

            let pension = (rate / 100) * mbq;

            if (this.hasMandatoryHistory && Number(this.mandatoryMonths || 0) >= 20 * 12 && this.currentParameterPeriod) {
                pension = Math.max(pension, Number(this.currentParameterPeriod.reference_level));
            }

            return { value: pension, reason: null, rate };
        },

        // ── Bảng minh hoạ "nếu tiếp tục đóng đến khi đủ điều kiện" ──────
        // Mở rộng theo yêu cầu: dù CHƯA đủ điều kiện (Bước 4), vẫn ước tính tương đối lương hưu
        // NẾU tiếp tục đóng thêm ở 1 trong 3 mức thu nhập giả định cho tới khi đủ số năm yêu
        // cầu. Hệ số trượt giá cho các năm tương lai lấy CỐ ĐỊNH bằng hệ số năm hiện tại
        // (currentYearCoefficient) — chưa có hệ số nào được BHXH Việt Nam công bố cho năm chưa
        // tới, xem projectPensionFor().
        get projectionBaseIncome() {
            const rows = this.sortedRows();
            if (!rows.length) return this.currentParameterPeriod ? Number(this.currentParameterPeriod.rural_poverty_line) : 1500000;
            return Number(rows[rows.length - 1].income) || 0;
        },

        resetProjectionIncomes() {
            const base = this.projectionBaseIncome;
            const period = this.currentParameterPeriod;
            const floor = period ? Number(period.rural_poverty_line) : 1500000;
            const ceiling = period ? Number(period.reference_level) * period.ceiling_multiplier : base * 2;
            const round = (v) => Math.round(v / 1000) * 1000;

            this.projectionIncomes = {
                lower: Math.max(floor, round(base * 0.7)),
                current: Math.min(ceiling, Math.max(floor, base)),
                higher: Math.min(ceiling, round(base * 1.3)),
            };
            // Bấm "Đặt lại" nghĩa là người dùng muốn quay về auto-sync theo Bước 2 — mở lại cờ
            // để $watch trong init() tiếp tục tự động cập nhật cho tới lần tự sửa tay kế tiếp.
            this.projectionIncomesTouched = false;
        },

        projectPensionFor(scenarioIncome) {
            const period = this.currentParameterPeriod;
            if (!period) return { blocked: true, reason: 'no_active_period' };

            const breakdown = this.averageIncomeBreakdown();
            if (breakdown.some((b) => b.missingCoefficient)) return { blocked: true, reason: 'missing_coefficient' };

            const elig = this.pensionEligibility;
            const additionalMonthsNeeded = Math.max(0, elig.monthsRequired - elig.monthsAccumulated);

            const mandatoryMonths = this.hasMandatoryHistory ? Number(this.mandatoryMonths || 0) : 0;
            const mandatoryAmount = mandatoryMonths * Number(this.mandatoryAverageIncome || 0);
            const currentVoluntaryAdjusted = breakdown.reduce((sum, b) => sum + b.adjustedTotal, 0);
            const currentTotalMonths = this.totalVoluntaryMonths + mandatoryMonths;

            // hệ số tương lai = hệ số của năm hiện tại (currentYearCoefficient — cố định cho mọi
            // năm chưa tới, theo yêu cầu người dùng 2026-08-05; xem comment tại getter đó)
            const futureCoefficient = this.currentYearCoefficient;
            const projectedTotalMonths = currentTotalMonths + additionalMonthsNeeded;
            const projectedNumerator = mandatoryAmount + currentVoluntaryAdjusted + (additionalMonthsNeeded * scenarioIncome * futureCoefficient);
            const projectedMbq = projectedTotalMonths > 0 ? projectedNumerator / projectedTotalMonths : null;

            const projectedYears = Math.floor((elig.monthsAccumulated + additionalMonthsNeeded) / 12);
            const rate = this.pensionRateFor(this.gender, projectedYears);

            let projectedPension = null;
            if (rate !== null && projectedMbq !== null) {
                projectedPension = (rate / 100) * projectedMbq;
                if (this.hasMandatoryHistory && mandatoryMonths >= 20 * 12) {
                    projectedPension = Math.max(projectedPension, Number(period.reference_level));
                }
            }

            // Bảng minh hoạ chi tiết THEO TỪNG NĂM cho số tháng còn thiếu (yêu cầu người dùng
            // 2026-08-05) — gộp các tháng đóng thêm theo năm dương lịch (tính từ tháng kế tiếp
            // tháng hiện tại), mỗi năm dùng NGUYÊN mức thu nhập kịch bản × futureCoefficient.
            const yearlyRows = [];
            let cumulativeMonths = currentTotalMonths;
            let cumulativeAdjusted = mandatoryAmount + currentVoluntaryAdjusted;
            for (let offset = 1; offset <= additionalMonthsNeeded; offset++) {
                const { year } = this.monthYearForOffset(offset);
                let row = yearlyRows[yearlyRows.length - 1];
                if (!row || row.year !== year) {
                    row = { year, months: 0, coefficient: futureCoefficient, amount: 0 };
                    yearlyRows.push(row);
                }
                const monthAmount = scenarioIncome * futureCoefficient;
                row.months += 1;
                row.amount += monthAmount;
                cumulativeMonths += 1;
                cumulativeAdjusted += monthAmount;
                row.cumulativeMonths = cumulativeMonths;
                row.cumulativeAdjusted = cumulativeAdjusted;
            }

            return {
                blocked: false,
                additionalMonthsNeeded,
                eligibleAtLabel: this.monthsFromNowLabel(additionalMonthsNeeded),
                projectedAgeAtEligibility: this.currentAge !== null ? this.currentAge + Math.ceil(additionalMonthsNeeded / 12) : null,
                projectedYears,
                projectedMbq,
                projectedPension,
                needsVerifiedRateTable: rate === null,
                monthlyContribution: this.monthlyContributionForIncome(scenarioIncome, 'other', this.supportMonthsUsed, period),
                yearlyRows,
            };
        },

        // ── §15 — Phase 1.5: Dự báo & Tối ưu mức đóng ───────────────
        monthlyContributionForIncome(income, supportGroup, priorSupportMonths, period) {
            const grossRate = period.contribution_rate_percent / 100;
            const gross = income * grossRate;

            if (supportGroup === 'none' || priorSupportMonths >= 120) {
                return { gross, support: 0, net: gross };
            }

            const tier = (period.support_tiers || []).find((t) => t.group_key === supportGroup);
            const supportPercent = tier ? tier.support_percent / 100 : 0;
            const support = supportPercent * (grossRate * period.rural_poverty_line);

            return { gross, support, net: gross - support };
        },

        // applyMandatoryFloor: tham số RIÊNG (không đọc this.optimizer.hasMandatory20Years trực
        // tiếp) để hàm này tái dùng an toàn cho các form khác (VD illustrationResultFor) mà
        // không bị dính trạng thái checkbox của form "Dự báo & Tối ưu" phía trên.
        findMinimumIncomeForTarget(targetPension, years, gender, supportGroup, priorSupportMonths, applyMandatoryFloor = false) {
            const period = this.currentParameterPeriod;
            if (!period) return { error: 'no_active_period' };

            let lo = Number(period.rural_poverty_line);
            let hi = Number(period.reference_level) * period.ceiling_multiplier;
            const EPSILON = 1000; // đồng — §15.3

            const estimatedPensionFor = (tn) => {
                const rate = this.pensionRateFor(gender, years);
                if (rate === null) return null;
                let value = (rate / 100) * tn;
                if (applyMandatoryFloor) value = Math.max(value, Number(period.reference_level));
                return value;
            };

            if (estimatedPensionFor(hi) === null) return { needsVerifiedRateTable: true };
            if (estimatedPensionFor(hi) < targetPension) return { achievable: false, maxPossiblePension: estimatedPensionFor(hi) };

            while (hi - lo > EPSILON) {
                const mid = (lo + hi) / 2;
                if (estimatedPensionFor(mid) >= targetPension) hi = mid; else lo = mid;
            }

            const ceiling = Number(period.reference_level) * period.ceiling_multiplier;
            const requiredIncome = Math.min(Math.max(hi, period.rural_poverty_line), ceiling);

            const monthly = this.monthlyContributionForIncome(requiredIncome, supportGroup, priorSupportMonths, period);
            const totalMonths = years * 12;
            const supportMonthsAvailable = Math.max(0, 120 - priorSupportMonths);
            const monthsWithSupport = Math.min(totalMonths, supportMonthsAvailable);
            const monthsWithoutSupport = totalMonths - monthsWithSupport;

            return {
                achievable: true,
                requiredIncome,
                monthlyContribution: monthly,
                totalCostEstimate: monthly.net * monthsWithSupport + monthly.gross * monthsWithoutSupport,
                willExhaustSupport: monthsWithoutSupport > 0,
                monthsWithSupport,
                monthsWithoutSupport,
            };
        },

        runOptimizer() {
            this.optimizer.result = this.findMinimumIncomeForTarget(
                Number(this.optimizer.targetPension),
                Number(this.optimizer.years),
                this.gender,
                this.optimizer.supportGroup,
                Number(this.optimizer.priorSupportMonths || 0),
                this.optimizer.hasMandatory20Years
            );
        },

        // Bảng minh hoạ theo tuổi bắt đầu tham gia — priorSupportMonths=0 và applyMandatoryFloor
        // =false (cohort giả định thuần tự nguyện, mới bắt đầu, chưa có lịch sử BHXH bắt buộc).
        illustrationResultFor(target, years, gender) {
            return this.findMinimumIncomeForTarget(
                Number(target),
                Number(years),
                gender,
                this.illustration.supportGroup,
                0,
                false
            );
        },
    }));
});
</script>
@endpush
