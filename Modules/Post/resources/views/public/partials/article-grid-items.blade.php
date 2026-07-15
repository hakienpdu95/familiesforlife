{{-- Dãy <x-frontend.article-card> thuần (không bọc <section>) — dùng chung bởi khối lưới
     "Thêm Bài Viết" (Modules/Post/resources/views/public/home.blade.php, lần render đầu) và
     PublicCategoryController::loadMore() (JSON, nối thêm qua Alpine khi bấm "Xem thêm"). --}}
@foreach($articles as $t)
<x-frontend.article-card :translation="$t" size="sm" />
@endforeach
