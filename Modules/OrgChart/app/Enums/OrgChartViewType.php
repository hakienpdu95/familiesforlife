<?php

namespace Modules\OrgChart\Enums;

enum OrgChartViewType: string
{
    case Tree     = 'tree';
    case FlatList = 'flat_list';
    case Matrix   = 'matrix';

    public function label(): string
    {
        return match($this) {
            self::Tree     => 'Cây phân cấp',
            self::FlatList => 'Danh sách phẳng',
            self::Matrix   => 'Ma trận',
        };
    }
}
