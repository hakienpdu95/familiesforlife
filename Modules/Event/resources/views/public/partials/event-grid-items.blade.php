{{-- Dãy <x-frontend.event-card> thuần (không bọc <section>) — dùng chung bởi khối lưới ban đầu
     (Modules/Event/resources/views/public/{index,category}.blade.php) và
     PublicEventController::loadMore() (JSON, nối thêm qua Alpine khi bấm "Xem thêm"). --}}
@foreach($events as $event)
<x-frontend.event-card :event="$event" size="sm" />
@endforeach
