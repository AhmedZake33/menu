@extends('layouts.panel')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <span class="eyebrow text-primary">إدارة المنيو</span>
        <h1 class="mb-0">الأصناف</h1>
    </div>
    <span class="badge text-bg-light">{{ $items->total() }} صنف</span>
</div>

<div class="card create-panel mb-4">
    <div class="create-panel-head">
        <div>
            <h2><i class="bi bi-plus-circle"></i> إضافة صنف جديد</h2>
            <p>أضف الصنف بسرعة مع السعر والتصنيف والصورة.</p>
        </div>
    </div>
    <form method="post" enctype="multipart/form-data" action="{{ route('dashboard.items.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-lg-4">
                <label class="form-label">اسم الصنف</label>
                <input class="form-control" name="name" required placeholder="مثال: لاتيه مثلج">
            </div>
            <div class="col-lg-4">
                <label class="form-label">التصنيف</label>
                <select class="form-select" name="category_id" required>
                    <option value="">اختر التصنيف</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->menuPage->name }} - {{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">السعر</label>
                <input class="form-control" type="number" step=".01" min="0" name="price" required placeholder="0.00">
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">الترتيب</label>
                <input class="form-control" type="number" min="0" name="sort_order" value="0">
            </div>
            <div class="col-lg-8">
                <label class="form-label">وصف مختصر</label>
                <input class="form-control" name="short_description" placeholder="وصف يظهر تحت اسم الصنف في المنيو">
            </div>
            <div class="col-lg-4">
                <label class="form-label">صورة الصنف</label>
                <input class="form-control" type="file" name="image" accept="image/*">
            </div>
            <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="item-flags create-flags">
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                        <span>ظاهر</span>
                    </label>
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_available" value="1" checked>
                        <span>متوفر</span>
                    </label>
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_featured" value="1">
                        <span>مميز</span>
                    </label>
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_new" value="1">
                        <span>جديد</span>
                    </label>
                </div>
                <button class="btn btn-primary px-4">
                    <i class="bi bi-plus-lg"></i> إضافة الصنف
                </button>
            </div>
        </div>
    </form>
</div>

<div class="card overflow-hidden">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>الصنف</th>
                    <th>التصنيف</th>
                    <th>السعر</th>
                    <th>الحالة</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                @if($item->image)
                                    <button class="item-thumb item-thumb-button" type="button" data-bs-toggle="modal" data-bs-target="#viewItemImage{{ $item->id }}" aria-label="عرض صورة {{ $item->name }}">
                                        <img src="{{ Storage::url($item->image) }}" alt="{{ $item->name }}">
                                    </button>
                                @else
                                    <div class="item-thumb"><i class="bi bi-image"></i></div>
                                @endif
                                <div>
                                    <strong>{{ $item->name }}</strong>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        @if($item->is_featured)<span class="badge text-bg-warning">مميز</span>@endif
                                        @if($item->is_new)<span class="badge text-bg-success">جديد</span>@endif
                                        @unless($item->is_active)<span class="badge text-bg-secondary">مخفي</span>@endunless
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $item->category->name }}</td>
                        <td>
                            {{ number_format($item->price, 2) }} {{ auth()->user()->restaurant->currency }}
                            @if($item->old_price)
                                <small class="d-block text-muted text-decoration-line-through">{{ number_format($item->old_price, 2) }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $item->is_available ? 'text-bg-success' : 'text-bg-secondary' }}">
                                {{ $item->is_available ? 'متوفر' : 'غير متوفر' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                                @if($item->image)
                                    <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="modal" data-bs-target="#viewItemImage{{ $item->id }}">
                                        <i class="bi bi-arrows-fullscreen"></i> الصورة
                                    </button>
                                @endif
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#editItem{{ $item->id }}">
                                    <i class="bi bi-pencil"></i> تعديل
                                </button>
                                <form method="post" action="{{ route('dashboard.items.destroy', $item) }}">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('حذف الصنف؟')">
                                        <i class="bi bi-trash"></i> حذف
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    @if($item->image)
                        <div class="modal fade" id="viewItemImage{{ $item->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content image-preview-modal">
                                    <div class="modal-header">
                                        <h2 class="modal-title fs-5">صورة {{ $item->name }}</h2>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                                    </div>
                                    <div class="modal-body">
                                        <img src="{{ Storage::url($item->image) }}" alt="{{ $item->name }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="modal fade" id="editItem{{ $item->id }}" tabindex="-1" aria-labelledby="editItemLabel{{ $item->id }}" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <form method="post" enctype="multipart/form-data" action="{{ route('dashboard.items.update', $item) }}">
                                    @csrf
                                    @method('put')
                                    <div class="modal-header">
                                        <h2 class="modal-title fs-5" id="editItemLabel{{ $item->id }}">تعديل {{ $item->name }}</h2>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row g-3">
                                            <div class="col-md-5">
                                                <label class="form-label">الصورة</label>
                                                <div class="item-image-editor">
                                                    @if($item->image)
                                                        <img src="{{ Storage::url($item->image) }}" alt="{{ $item->name }}">
                                                    @else
                                                        <div><i class="bi bi-image"></i><span>لا توجد صورة</span></div>
                                                    @endif
                                                </div>
                                                <input class="form-control mt-2" type="file" name="image" accept="image/*">
                                                @if($item->image)
                                                    <button class="btn btn-outline-danger w-100 mt-2" type="submit" form="deleteItemImage{{ $item->id }}" onclick="return confirm('حذف صورة الصنف؟')">
                                                        <i class="bi bi-image"></i> حذف الصورة
                                                    </button>
                                                @endif
                                            </div>
                                            <div class="col-md-7">
                                                <div class="row g-3">
                                                    <div class="col-12">
                                                        <label class="form-label">اسم الصنف</label>
                                                        <input class="form-control" name="name" required value="{{ old('name', $item->name) }}">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label">التصنيف</label>
                                                        <select class="form-select" name="category_id" required>
                                                            @foreach($categories as $category)
                                                                <option value="{{ $category->id }}" @selected(old('category_id', $item->category_id) == $category->id)>{{ $category->menuPage->name }} - {{ $category->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">السعر</label>
                                                        <input class="form-control" type="number" step=".01" min="0" name="price" required value="{{ old('price', $item->price) }}">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">السعر القديم</label>
                                                        <input class="form-control" type="number" step=".01" min="0" name="old_price" value="{{ old('old_price', $item->old_price) }}">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label">وصف مختصر</label>
                                                        <textarea class="form-control" name="short_description" rows="3">{{ old('short_description', $item->short_description) }}</textarea>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">الترتيب</label>
                                                        <input class="form-control" type="number" min="0" name="sort_order" value="{{ old('sort_order', $item->sort_order) }}">
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="item-flags">
                                                            @foreach(['is_active' => 'ظاهر', 'is_available' => 'متوفر', 'is_featured' => 'مميز', 'is_new' => 'جديد'] as $field => $label)
                                                                <label class="form-check form-switch">
                                                                    <input class="form-check-input" type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $item->$field))>
                                                                    <span>{{ $label }}</span>
                                                                </label>
                                                            @endforeach
                                                        </div>
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
                                @if($item->image)
                                    <form id="deleteItemImage{{ $item->id }}" method="post" action="{{ route('dashboard.items.image.destroy', $item) }}">
                                        @csrf
                                        @method('delete')
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <tr>
                        <td colspan="5" class="empty-state">لا توجد أصناف.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($items->hasPages())
        <div class="pagination-footer">
            {{ $items->onEachSide(1)->links() }}
        </div>
    @endif
</div>
@endsection
