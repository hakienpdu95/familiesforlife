{{-- spec/Newsletter_Technical_Specification.md §16 mục 3 — vị trí nhúng form chưa chốt (open
     question), nên đây là 1 partial độc lập, include được ở bất kỳ trang công khai nào:
     @include('newsletter::public._subscribe-form') --}}
<div class="card bg-base-100 border border-base-200 shadow-sm max-w-md">
    <div class="card-body p-5">
        <h3 class="font-bold text-base-content mb-1">Đăng ký nhận bản tin</h3>
        <p class="text-sm text-base-content/60 mb-4">Nhận bài viết mới nhất qua email, không spam.</p>

        @if(session('success'))
        <div class="alert alert-success text-sm py-2 px-3 mb-3">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('newsletter.public.subscribe') }}" class="space-y-2">
            @csrf
            <input type="text" name="full_name" value="{{ old('full_name') }}" placeholder="Họ và tên" required maxlength="150"
                   class="input input-bordered input-sm w-full @error('full_name') input-error @enderror">
            @error('full_name')<p class="text-xs text-error">{{ $message }}</p>@enderror

            <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" required maxlength="255"
                   class="input input-bordered input-sm w-full @error('email') input-error @enderror">
            @error('email')<p class="text-xs text-error">{{ $message }}</p>@enderror

            <button type="submit" class="btn btn-primary btn-sm w-full">Đăng ký</button>
        </form>
    </div>
</div>
