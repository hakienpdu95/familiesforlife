<?php namespace App\Metaboxes;

class PropertyForSaleMetabox extends BaseMetabox
{
    protected string $title = 'Thông tin chi tiết nhà đất bán';
    protected array $post_types = ['property-for-sale'];

    protected function getFields(): array
    {
        return [
            // ====================== VỊ TRÍ ======================
            ['type' => 'heading', 'name' => 'Vị trí bất động sản'],
            [
                'name' => 'Số nhà và đường',
                'id'   => 'address_detail',
                'type' => 'text',
                'desc' => 'Ví dụ: 12 Nguyễn Văn A, Phường 5',
            ],

            // ====================== GIÁ BÁN ======================
            ['type' => 'heading', 'name' => 'Giá bán'],
            [
                'name' => 'Giá bán (tỷ VND)',
                'id'   => 'price',
                'type' => 'number',
                'step' => '0.1',
            ],
            [
                'name' => 'Bán gấp',
                'id'   => 'urgent_sale',
                'type' => 'checkbox',
            ],
            [
                'name'  => 'Thời gian muốn bán nhanh (số ngày)',
                'id'    => 'urgent_days',
                'type'  => 'number',
                'desc'  => 'Ví dụ: 30 ngày',
                'visible' => ['urgent_sale', '=', 1],
            ],

            // ====================== LOẠI HÌNH ======================
            ['type' => 'heading', 'name' => 'Loại hình bất động sản'],
            [
                'name'    => 'Loại hình',
                'id'      => 'property_type',
                'type'    => 'select',
                'std'     => '',
                'options' => [
                    ''         => '-- Chọn loại hình --',
                    'house'    => 'Nhà riêng',
                    'apartment'=> 'Căn hộ chung cư',
                    'land'     => 'Đất thổ cư',
                ],
                'required' => true,
            ],

            // Sub types cũ
            [
                'name'    => 'Loại nhà riêng',
                'id'      => 'house_subtype',
                'type'    => 'select',
                'std'     => '',
                'options' => [
                    ''        => '-- Chọn loại nhà --',
                    'alley'   => 'Nhà hẻm',
                    'street'  => 'Nhà mặt tiền',
                    'adjacent'=> 'Nhà liền kề',
                    'villa'   => 'Biệt thự',
                ],
                'visible' => ['property_type', '=', 'house'],
            ],
            [
                'name'    => 'Loại căn hộ',
                'id'      => 'apartment_subtype',
                'type'    => 'select',
                'std'     => '',
                'options' => [
                    ''         => '-- Chọn loại căn hộ --',
                    'apartment'=> 'Căn hộ',
                    'officetel'=> 'Officetel',
                    'duplex'   => 'Duplex',
                    'penthouse'=> 'Penthouse',
                    'shophouse'=> 'Shophouse',
                ],
                'visible' => ['property_type', '=', 'apartment'],
            ],

            // ====================== THÔNG TIN NHÀ (MỚI) ======================
            ['type' => 'heading', 'name' => 'Thông tin nhà'],

            // === CHUNG NHÀ RIÊNG + ĐẤT THỔ CƯ ===
            [
                'name'  => 'Chiều ngang (m)',
                'id'    => 'width',
                'type'  => 'number',
                'step'  => '0.01',
                'min'   => 0,
                'desc'  => 'Ví dụ: 5.5',
            ],
            [
                'name'  => 'Chiều dài (m)',
                'id'    => 'length',
                'type'  => 'number',
                'step'  => '0.01',
                'min'   => 0,
                'desc'  => 'Ví dụ: 20',
            ],
            [
                'name'  => 'Diện tích đất công nhận (m²)',
                'id'    => 'land_area',
                'type'  => 'number',
                'step'  => '0.1',
                'min'   => 0,
            ],

            // === CHỈ CĂN HỘ CHUNG CƯ ===
            [
                'name'  => 'Diện tích tim tường (m²)',
                'id'    => 'usable_area',
                'type'  => 'number',
                'step'  => '0.1',
                'min'   => 0,
                'desc'  => 'Diện tích sử dụng thực tế',
            ],

            // === GIẤY TỜ PHÁP LÝ (tách riêng 3 field để options đúng từng loại) ===
            [
                'name'    => 'Giấy tờ pháp lý',
                'id'      => 'legal_house',
                'type'    => 'select',
                'std'     => '',
                'options' => [
                    ''               => '-- Chọn loại giấy tờ --',
                    'so_hong_rieng'  => 'Sổ hồng riêng',
                    'so_hong_chung'  => 'Sổ hồng chung',
                    'dang_hoan_cong' => 'Đang làm hoàn công',
                ],
            ],
            [
                'name'    => 'Giấy tờ pháp lý',
                'id'      => 'legal_apartment',
                'type'    => 'select',
                'std'     => '',
                'options' => [
                    ''               => '-- Chọn loại giấy tờ --',
                    'so_hong_rieng'  => 'Sổ hồng riêng',
                    'hop_dong'       => 'Hợp đồng mua bán',
                    'dang_cho_cap_so'=> 'Đang chờ cấp sổ',
                ],
            ],
            [
                'name'    => 'Giấy tờ pháp lý',
                'id'      => 'legal_land',
                'type'    => 'select',
                'std'     => '',
                'options' => [
                    ''               => '-- Chọn loại giấy tờ --',
                    'so_hong_rieng'  => 'Sổ hồng riêng',
                    'so_hong_chung'  => 'Sổ hồng chung',
                ],
            ],

            // === CHỈ NHÀ RIÊNG ===
            [
                'name'    => 'Hướng nhà (cửa chính)',
                'id'      => 'direction',
                'type'    => 'select',
                'std'     => '',
                'options' => [
                    ''          => '-- Chọn hướng --',
                    'tay_bac'   => 'Tây Bắc',
                    'bac'       => 'Bắc',
                    'dong_bac'  => 'Đông Bắc',
                    'tay'       => 'Tây',
                    'dong'      => 'Đông',
                    'tay_nam'   => 'Tây Nam',
                    'nam'       => 'Nam',
                    'dong_nam'  => 'Đông Nam',
                ],
            ],
            [
                'name' => 'Số tầng',
                'id'   => 'floors',
                'type' => 'number',
                'min'  => 1,
            ],

            // === CHUNG NHÀ RIÊNG + CĂN HỘ ===
            [
                'name' => 'Số phòng ngủ',
                'id'   => 'bedrooms',
                'type' => 'number',
                'min'  => 0,
            ],
            [
                'name' => 'Số phòng tắm',
                'id'   => 'bathrooms',
                'type' => 'number',
                'min'  => 0,
            ],

            // ====================== THÔNG TIN KHÁC (MỚI) ======================
            ['type' => 'heading', 'name' => 'Thông tin khác'],

            // === CHỈ CĂN HỘ CHUNG CƯ ===
            [
                'name' => 'Tên dự án / tòa nhà',
                'id'   => 'project_name',
                'type' => 'text',
                'desc' => 'Ví dụ: Chung cư Sunshine City',
            ],
            [
                'name' => 'Địa chỉ cụ thể',
                'id'   => 'apartment_address',
                'type' => 'text',
                'desc' => 'Số căn - Tầng - Block, ví dụ: Căn 1203 - Tầng 12 - Block A',
            ],
            [
                'name'  => 'Diện tích thông thuỷ (m²)',
                'id'    => 'net_area',
                'type'  => 'number',
                'step'  => '0.1',
                'min'   => 0,
            ],
            [
                'name'    => 'Tình trạng sử dụng',
                'id'      => 'usage_status',
                'type'    => 'select',
                'std'     => '',
                'options' => [
                    ''              => '-- Chọn tình trạng --',
                    'dang_o'        => 'Đang ở',
                    'dang_cho_thue' => 'Đang cho thuê',
                    'nha_trong'     => 'Nhà trống',
                ],
            ],
            [
                'name'    => 'Nội thất',
                'id'      => 'interior_status',
                'type'    => 'select',
                'std'     => '',
                'options' => [
                    ''              => '-- Chọn nội thất --',
                    'day_du'        => 'Nội thất đầy đủ',
                    'co_ban'        => 'Nội thất cơ bản',
                    'ban_giao_tho'  => 'Bàn giao thô',
                ],
            ],
            [
                'name'    => 'Hướng cửa chính',
                'id'      => 'apartment_direction',
                'type'    => 'select',
                'std'     => '',
                'options' => [
                    ''          => '-- Chọn hướng --',
                    'tay_bac'   => 'Tây Bắc',
                    'bac'       => 'Bắc',
                    'dong_bac'  => 'Đông Bắc',
                    'tay'       => 'Tây',
                    'dong'      => 'Đông',
                    'tay_nam'   => 'Tây Nam',
                    'nam'       => 'Nam',
                    'dong_nam'  => 'Đông Nam',
                ],
            ],
            [
                'name'    => 'Hướng ban công',
                'id'      => 'balcony_direction',
                'type'    => 'select',
                'std'     => '',
                'options' => [
                    ''         => '-- Chọn hướng ban công --',
                    'dong'     => 'Đông',
                    'tay_nam'  => 'Tây Nam',
                    'nam'      => 'Nam',
                    'dong_nam' => 'Đông Nam',
                ],
            ],

            // === CHỈ ĐẤT THỔ CƯ ===
            [
                'name'  => 'Đường trước nhà/đất (m)',
                'id'    => 'front_road_width',
                'type'  => 'number',
                'step'  => '0.1',
                'min'   => 0,
                'desc'  => 'Mặt tiền kinh doanh / nội bộ',
            ],
            [
                'name'    => 'Hướng',
                'id'      => 'land_direction',
                'type'    => 'select',
                'std'     => '',
                'options' => [
                    ''          => '-- Chọn hướng --',
                    'tay_bac'   => 'Tây Bắc',
                    'bac'       => 'Bắc',
                    'dong_bac'  => 'Đông Bắc',
                    'tay'       => 'Tây',
                    'dong'      => 'Đông',
                    'tay_nam'   => 'Tây Nam',
                    'nam'       => 'Nam',
                    'dong_nam'  => 'Đông Nam',
                ],
            ],

            // === FIELD ẨN/HIỆN THEO TÌNH TRẠNG (chỉ Căn hộ) ===
            [
                'name'  => 'Giá cho thuê mỗi tháng (VNĐ)',
                'id'    => 'monthly_rent',
                'type'  => 'number',
                'step'  => '100000',
                'min'   => 0,
                'desc'  => 'Ví dụ: 15.000.000',
            ],
            // ====================== HÌNH ẢNH BẤT ĐỘNG SẢN (MỚI) ======================
            ['type' => 'heading', 'name' => 'Hình ảnh bất động sản'],

            [
                'name'             => 'Hình ảnh nhà (tối đa 6 ảnh)',
                'id'               => 'property_images',
                'type'             => 'image_advanced',
                'max_file_uploads' => 6,
                'max_status'       => true,      // hiển thị "x/6" rất tiện
                'image_size'       => 'medium',
                'desc'             => 'Kéo thả để sắp xếp thứ tự. Ảnh đầu tiên thường làm ảnh đại diện.',
                'force_delete'     => false,
            ],
        ];
    }

    /**
     * JS FALLBACK SIÊU MẠNH – Xử lý cả 2 khối (Thông tin nhà + Thông tin khác) + nested conditional
     */
    public static function jsFallback(): void
    {
        ?>
        <script>
        jQuery(function($) {
            function toggleAll() {
                var type   = $('#property_type').val() || '';
                var urgent = $('#urgent_sale').is(':checked');
                var status = $('#usage_status').val() || '';

                // Ẩn tất cả field của 2 khối mới + cũ
                $('#urgent_days, #house_subtype, #apartment_subtype, #width, #length, #land_area, #usable_area, #legal_house, #legal_apartment, #legal_land, #direction, #floors, #bedrooms, #bathrooms, ' +
                   '#project_name, #apartment_address, #net_area, #usage_status, #interior_status, #apartment_direction, #balcony_direction, #front_road_width, #land_direction, #monthly_rent')
                    .closest('.rwmb-field').hide();

                // === SUB-TYPE cũ ===
                if (type === 'house') $('#house_subtype').closest('.rwmb-field').show();
                if (type === 'apartment') $('#apartment_subtype').closest('.rwmb-field').show();

                // === THÔNG TIN NHÀ (giữ nguyên) ===
                if (type === 'house') {
                    $('#width, #length, #land_area, #legal_house, #direction, #floors, #bedrooms, #bathrooms').closest('.rwmb-field').show();
                } else if (type === 'apartment') {
                    $('#usable_area, #legal_apartment, #bedrooms, #bathrooms').closest('.rwmb-field').show();
                } else if (type === 'land') {
                    $('#width, #length, #land_area, #legal_land').closest('.rwmb-field').show();
                }

                // === THÔNG TIN KHÁC (MỚI) ===
                if (type === 'apartment') {
                    $('#project_name, #apartment_address, #net_area, #usage_status, #interior_status, #apartment_direction, #balcony_direction')
                        .closest('.rwmb-field').show();

                    // Nested: Đang cho thuê → hiện giá thuê
                    if (status === 'dang_cho_thue') {
                        $('#monthly_rent').closest('.rwmb-field').show();
                    }
                } else if (type === 'land') {
                    $('#front_road_width, #land_direction').closest('.rwmb-field').show();
                }

                // === HÌNH ẢNH LUÔN HIỂN THỊ ===
                $('#property_images').closest('.rwmb-field').show();

                // === BÁN GẤP ===
                if (urgent) $('#urgent_days').closest('.rwmb-field').show();
            }

            // === CHẠY NGAY + THEO DÕI ===
            toggleAll();
            $('#property_type, #usage_status').on('change', toggleAll);
            $('#urgent_sale').on('change', toggleAll);

            // === LOAD ĐÚNG KHI CHỈNH SỬA BÀI CŨ ===
            $(document).on('rwmb_ready', toggleAll);
            setTimeout(toggleAll, 400); // an toàn hơn
        });
        </script>
        <?php
    }

    public function __construct()
    {
        parent::__construct();
        add_action('rwmb_after', [self::class, 'jsFallback']);

        add_action('rwmb_after_save_post', function ($post_id) {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }

            $post_type = get_post_type($post_id);

            if ($post_type === 'property-for-sale' && isset($_POST['property_images'])) {
                $images = $_POST['property_images'];
                $images = is_array($images) ? array_map('intval', $images) : [];
                update_post_meta($post_id, 'property_images', $images);
            }
        }, 20);
    }
}