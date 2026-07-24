<?php namespace App\Metaboxes;

class PropertyForRentMetabox extends BaseMetabox
{
    protected string $title = 'Thông tin chi tiết nhà đất thuê';
    protected array $post_types = ['property-for-rent'];

    protected function getFields(): array
    {
        return [
            // ====================== VỊ TRÍ ======================
            ['type' => 'heading', 'name' => 'Vị trí bất động sản'],
            [
                'name' => 'Số nhà và đường',
                'id'   => 'rent_address_detail',
                'type' => 'text',
                'desc' => 'Ví dụ: 12 Nguyễn Văn A, Phường 5',
            ],

            [
                'name'    => 'Loại hình',
                'id'      => 'rent_property_type',
                'type'    => 'select',
                'std'     => '',
                'options' => [
                    ''         => '-- Chọn loại hình --',
                    'house'    => 'Nhà riêng',
                    'apartment'=> 'Căn hộ chung cư',
                    'layout'   => 'Mặt bằng',
                ],
                'required' => true,
            ],
            [
                'name'  => 'Diện tích sử dụng (m²)',
                'id'    => 'rent_usable_area',
                'type'  => 'number',
                'step'  => '0.1',
                'min'   => 0,
            ],
            [
                'name'  => 'Thời hạn cho thuê (tháng)',
                'id'    => 'rent_rental_period',
                'type'  => 'number',
                'min'   => 3,
                'desc'  => 'Tối thiểu 3 tháng',
            ],
            [
                'name'  => 'Giá cho thuê theo tháng (VNĐ)',
                'id'    => 'rent_monthly_rent',
                'type'  => 'number',
                'step'  => '100000',
                'min'   => 0,
            ],
            [
                'name'  => 'Tiền cọc tối thiểu (VNĐ)',
                'id'    => 'rent_deposit',
                'type'  => 'number',
                'step'  => '100000',
                'min'   => 0,
            ],

            ['type' => 'heading', 'name' => 'Thông tin mặt bằng cho thuê'],

            [
                'name'    => 'Nội thất',
                'id'      => 'rent_interior_status',
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
                'name' => 'Số tầng',
                'id'   => 'rent_floors',
                'type' => 'number',
                'min'  => 1,
            ],
            [
                'name' => 'Số phòng ngủ',
                'id'   => 'rent_bedrooms',
                'type' => 'number',
                'min'  => 0,
            ],
            [
                'name' => 'Số phòng tắm',
                'id'   => 'rent_bathrooms',
                'type' => 'number',
                'min'  => 0,
            ],
            // ====================== HÌNH ẢNH BẤT ĐỘNG SẢN (MỚI) ======================
            ['type' => 'heading', 'name' => 'Hình ảnh bất động sản'],

            [
                'name'             => 'Hình ảnh nhà (tối đa 6 ảnh)',
                'id'               => 'rent_property_images',
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
     * JS FALLBACK RIÊNG BIỆT – Không còn xung đột với metabox bán
     */
    public static function jsFallback(): void
    {
        ?>
        <script>
        jQuery(function($) {
            // Bảo vệ: chỉ chạy khi là metabox thuê
            if (!$('#rent_property_type').length) return;

            function toggleRentFields() {
                var type = $('#rent_property_type').val() || '';

                // Ẩn tất cả field conditional + field chung
                $('#rent_interior_status, #rent_floors, #rent_bedrooms, #rent_bathrooms')
                    .closest('.rwmb-field').hide();

                if (type === '') return;

                // === Conditional theo loại hình ===
                if (type === 'house') {
                    // Nhà riêng: Nội thất + Số tầng + Phòng ngủ + Phòng tắm
                    $('#rent_interior_status, #rent_floors, #rent_bedrooms, #rent_bathrooms')
                        .closest('.rwmb-field').show();
                } 
                else if (type === 'apartment') {
                    // Căn hộ: Nội thất + Phòng ngủ + Phòng tắm (không có số tầng)
                    $('#rent_interior_status, #rent_bedrooms, #rent_bathrooms')
                        .closest('.rwmb-field').show();
                }
                // Mặt bằng (layout): chỉ giữ 4 field cơ bản

                // === HÌNH ẢNH LUÔN HIỂN THỊ ===
                $('#rent_property_images').closest('.rwmb-field').show();
            }

            // Khởi chạy
            toggleRentFields();
            $('#rent_property_type').on('change', toggleRentFields);

            // Load khi chỉnh sửa bài cũ
            $(document).on('rwmb_ready', toggleRentFields);
            setTimeout(toggleRentFields, 450);
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

            if ($post_type === 'property-for-rent' && isset($_POST['rent_property_images'])) {
                $images = $_POST['rent_property_images'];
                $images = is_array($images) ? array_map('intval', $images) : [];
                update_post_meta($post_id, 'rent_property_images', $images);
            }
        }, 20);
    }
}