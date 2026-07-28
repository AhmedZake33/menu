@extends('layouts.panel')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <span class="eyebrow text-primary">تنظيم المنيو</span>
        <h1 class="mb-0">التصنيفات</h1>
    </div>
    <span class="badge text-bg-light">{{ $categories->total() }} تصنيف</span>
</div>

<div class="card create-panel mb-4">
    <div class="create-panel-head">
        <div>
            <h2><i class="bi bi-folder-plus"></i> إضافة تصنيف جديد</h2>
            <p>استخدم التصنيفات لتقسيم الأصناف داخل كل صفحة منيو.</p>
        </div>
    </div>
    <form method="post" enctype="multipart/form-data" action="{{ route('dashboard.categories.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-lg-3">
                <label class="form-label">اسم التصنيف</label>
                <input class="form-control" name="name" required placeholder="مثال: مشروبات ساخنة">
            </div>
            <div class="col-lg-3">
                <label class="form-label">الرابط المختصر</label>
                <input class="form-control" name="slug" required dir="ltr" placeholder="hot-drinks">
            </div>
            <div class="col-lg-3">
                <label class="form-label">صفحة المنيو</label>
                <select class="form-select" name="menu_page_id" required>
                    <option value="">اختر الصفحة</option>
                    @foreach($pages as $page)
                        <option value="{{ $page->id }}">{{ $page->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label">الصورة</label>
                <input class="form-control" type="file" name="image" accept="image/*">
            </div>
            <div class="col-lg-9">
                <label class="form-label">الوصف</label>
                <input class="form-control" name="description" placeholder="وصف قصير للتصنيف">
            </div>
            <div class="col-lg-3">
                <label class="form-label">الترتيب</label>
                <input class="form-control" type="number" min="0" name="sort_order" value="0">
            </div>
            <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <label class="form-check form-switch create-toggle">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                    <span>تصنيف ظاهر</span>
                </label>
                <button class="btn btn-primary px-4">
                    <i class="bi bi-plus-lg"></i> إضافة التصنيف
                </button>
            </div>
        </div>
    </form>
</div>

<div class="row g-3">
    @forelse($categories as $category)
        <div class="col-md-6 col-xl-4">
            <div class="card category-card h-100">
                <div class="category-cover">
                    @if($category->image)
                        <img src="{{ Storage::url($category->image) }}" alt="{{ $category->name }}">
                    @else
                        <div><i class="bi bi-tags"></i></div>
                    @endif
                    <span class="badge {{ $category->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                        {{ $category->is_active ? 'نشط' : 'مخفي' }}
                    </span>
                </div>
                <div class="p-3">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <h2 class="h5 mb-1">{{ $category->name }}</h2>
                            <small class="text-muted">{{ $category->menuPage->name }} - {{ $category->slug }}</small>
                        </div>
                        <strong class="category-count">{{ $category->items_count }}</strong>
                    </div>
                    @if($category->description)
                        <p class="text-muted mt-3 mb-0">{{ $category->description }}</p>
                    @endif
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#editCategory{{ $category->id }}">
                            <i class="bi bi-pencil"></i> تعديل
                        </button>
                        <form method="post" action="{{ route('dashboard.categories.destroy', $category) }}">
                            @csrf
                            @method('delete')
                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('حذف التصنيف؟')">
                                <i class="bi bi-trash"></i> حذف
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editCategory{{ $category->id }}" tabindex="-1" aria-labelledby="editCategoryLabel{{ $category->id }}" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="post" enctype="multipart/form-data" action="{{ route('dashboard.categories.update', $category) }}">
                        @csrf
                        @method('put')
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="editCategoryLabel{{ $category->id }}">تعديل {{ $category->name }}</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label">الصورة</label>
                                    <div class="item-image-editor">
                                        @if($category->image)
                                            <img src="{{ Storage::url($category->image) }}" alt="{{ $category->name }}">
                                        @else
                                            <div><i class="bi bi-image"></i><span>لا توجد صورة</span></div>
                                        @endif
                                    </div>
                                    <input class="form-control mt-2" type="file" name="image" accept="image/*">
                                </div>
                                <div class="col-md-7">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">اسم التصنيف</label>
                                            <input class="form-control" name="name" required value="{{ old('name', $category->name) }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">الرابط المختصر</label>
                                            <input class="form-control" name="slug" required dir="ltr" value="{{ old('slug', $category->slug) }}">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">صفحة المنيو</label>
                                            <select class="form-select" name="menu_page_id" required>
                                                @foreach($pages as $page)
                                                    <option value="{{ $page->id }}" @selected(old('menu_page_id', $category->menu_page_id) == $page->id)>{{ $page->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">الوصف</label>
                                            <textarea class="form-control" name="description" rows="3">{{ old('description', $category->description) }}</textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">الترتيب</label>
                                            <input class="form-control" type="number" min="0" name="sort_order" value="{{ old('sort_order', $category->sort_order) }}">
                                        </div>
                                        <div class="col-md-6 d-flex align-items-end">
                                            <label class="form-check form-switch create-toggle mb-0 w-100">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active))>
                                                <span>تصنيف ظاهر</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button class="btn btn-primary"><i class="bi bi-check2"></i> حفظ التعديل</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="empty-state card">لا توجد تصنيفات.</div>
        </div>
    @endforelse
</div>

@if($categories->hasPages())
    <div class="pagination-footer mt-3 rounded-3">
        {{ $categories->onEachSide(1)->links() }}
    </div>
@endif
@endsection
