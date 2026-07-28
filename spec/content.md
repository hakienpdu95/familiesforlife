<div x-data="{ get cat() { return selectedCategory() } }" class="flex flex-col gap-3">
                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Trọng tâm nội dung chuyên mục</span></label>
                        <textarea x-model="cat._form.core_focus" rows="2" placeholder="VD: Kiến thức ăn dặm khoa học cho trẻ 6-24 tháng" class="textarea textarea-bordered textarea-sm w-full"></textarea>
                    </div>
                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Góc nhìn khác biệt (điều chỉ chuyên mục này viết được)</span></label>
                        <textarea x-model="cat._form.unique_angle" rows="2" placeholder="VD: Có đội ngũ chuyên gia dinh dưỡng nội bộ kiểm duyệt, không chỉ dịch lại nguồn ngoại" class="textarea textarea-bordered textarea-sm w-full"></textarea>
                    </div>
                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Mục tiêu nội dung</span></label>
                        <textarea x-model="cat._form.content_goals" rows="2" placeholder="VD: Tăng traffic tìm kiếm dài hạn, xây uy tín chuyên gia" class="textarea textarea-bordered textarea-sm w-full"></textarea>
                    </div>
                    <div class="form-control">
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">Pain points / câu hỏi thường gặp của độc giả (dựa trên nghiên cứu thực tế — khảo sát, feedback, câu hỏi lặp lại)</span>
                        </label>
                        <textarea x-model="cat._form.pain_points" rows="2" placeholder="VD: Con hay bị táo bón khi đổi sữa, mẹ không biết phân biệt sữa mát thật/giả" class="textarea textarea-bordered textarea-sm w-full"></textarea>
                    </div>
                    <div class="form-control">
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">Ý tưởng đã cân nhắc và quyết định KHÔNG viết (kèm lý do — Decision Log)</span>
                        </label>
                        <textarea x-model="cat._form.rejected_ideas" rows="2" placeholder="VD: 'So sánh giá sữa mát các hãng' — đã bỏ vì đối thủ đã làm rất kỹ, khó cạnh tranh" class="textarea textarea-bordered textarea-sm w-full"></textarea>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <div class="form-control flex-1 min-w-64">
                            <label class="label py-0.5"><span class="label-text text-xs font-medium">Đối tượng độc giả</span></label>
                            <input type="text" x-model="cat._form.audience" placeholder="VD: mẹ mới sinh con đầu lòng" class="input input-sm input-bordered w-full">
                        </div>
                        <div class="form-control flex-1 min-w-64">
                            <label class="label py-0.5"><span class="label-text text-xs font-medium">Ràng buộc / không muốn</span></label>
                            <input type="text" x-model="cat._form.constraints" placeholder="VD: không viết giọng hàn lâm" class="input input-sm input-bordered w-full">
                        </div>
                    </div>
                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Đoạn văn mẫu (giọng văn)</span></label>
                        <textarea x-model="cat._form.style_sample" rows="3" class="textarea textarea-bordered textarea-sm w-full text-xs"></textarea>
                    </div>
                    <div class="flex items-center gap-2 mt-1">
                        <button type="button" class="btn btn-primary btn-xs" :disabled="cat._saving" @click="save(cat)">
                            <span x-show="!cat._saving">Lưu</span>
                            <span x-show="cat._saving" style="display: none;">Đang lưu...</span>
                        </button>
                        <span x-show="cat._saved" class="text-success text-xs" style="display: none;">Đã lưu!</span>
                        <span x-show="cat._error" class="text-error text-xs" x-text="cat._error" style="display: none;"></span>
                    </div>
                </div>