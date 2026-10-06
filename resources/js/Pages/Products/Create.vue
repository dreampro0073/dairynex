<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'

defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
})

const form = useForm({
    name: '',
    category_id: '',
    sku: '',
    unit_type: 1,
    base_rate: '',
    status: 1,
})

const submit = () => {
    form.post('/products')
}
</script>

<template>
    <AdminLayout>
        <template #title>
            Add New Product
        </template>

        <template #subtitle>
            Add a product to your catalogue
        </template>

        <div class="product-form-page">
            <Link
                href="/products"
                class="back-btn"
            >
                <i class="bi bi-arrow-left"></i>
                Back to Products & Rates
            </Link>

            <form @submit.prevent="submit">
                <div class="form-card">
                    <div class="form-card-title">
                        Add New Product
                    </div>

                    <div class="form-body">
                        <div class="mb-3">
                            <label class="form-label">
                                Product Name
                            </label>

                            <input
                                v-model="form.name"
                                type="text"
                                class="form-control"
                                :class="{ 'is-invalid': form.errors.name }"
                                placeholder="e.g. Cow Ghee"
                            />

                            <div class="invalid-feedback">
                                {{ form.errors.name }}
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">
                                    Category
                                </label>

                                <select
                                    v-model="form.category_id"
                                    class="form-select"
                                    :class="{ 'is-invalid': form.errors.category_id }"
                                >
                                    <option value="">
                                        Select category
                                    </option>

                                    <option
                                        v-for="category in categories"
                                        :key="category.id"
                                        :value="category.id"
                                    >
                                        {{ category.name }}
                                    </option>
                                </select>

                                <div class="invalid-feedback">
                                    {{ form.errors.category_id }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    SKU
                                </label>

                                <input
                                    v-model="form.sku"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.sku }"
                                    placeholder="e.g. GHE-500"
                                />

                                <div class="invalid-feedback">
                                    {{ form.errors.sku }}
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">
                                    Unit
                                </label>

                                <select
                                    v-model.number="form.unit_type"
                                    class="form-select"
                                >
                                    <option :value="1">
                                        Litre (L)
                                    </option>

                                    <option :value="2">
                                        Millilitre (ml)
                                    </option>

                                    <option :value="3">
                                        Kilogram (kg)
                                    </option>

                                    <option :value="4">
                                        Gram (g)
                                    </option>

                                    <option :value="5">
                                        Piece
                                    </option>

                                    <option :value="6">
                                        Packet
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    Rate (₹)
                                </label>

                                <input
                                    v-model="form.base_rate"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.base_rate }"
                                    placeholder="e.g. 60"
                                />

                                <div class="invalid-feedback">
                                    {{ form.errors.base_rate }}
                                </div>
                            </div>
                        </div>

                        <div class="active-row">
                            <div>
                                <div class="active-title">
                                    Active
                                </div>

                                <div class="active-subtitle">
                                    Available for customer selection
                                </div>
                            </div>

                            <label class="switch">
                                <input
                                    type="checkbox"
                                    :checked="form.status === 1"
                                    @change="form.status = $event.target.checked ? 1 : 0"
                                />

                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <button
                    type="submit"
                    class="save-product-btn"
                    :disabled="form.processing"
                >
                    <span
                        v-if="form.processing"
                        class="spinner-border spinner-border-sm"
                    ></span>

                    <i
                        v-else
                        class="bi bi-floppy"
                    ></i>

                    Save Product
                </button>
            </form>
        </div>
    </AdminLayout>
</template>

<style scoped>
.product-form-page {
    width: 100%;
}

.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 7px 13px;
    margin-bottom: 14px;
    border: 1px solid #d6deea;
    border-radius: 10px;
    background: #ffffff;
    color: #1b3152;
    font-size: 11px;
    font-weight: 600;
    text-decoration: none;
}

.form-card {
    background: #ffffff;
    border-radius: 18px;
    overflow: hidden;
}

.form-card-title {
    height: 50px;
    display: flex;
    align-items: center;
    padding: 0 19px;
    border-bottom: 1px solid #e6ebf2;
    font-weight: 700;
    font-size: 14px;
    color: #17294a;
}

.form-body {
    padding: 18px;
}

.form-label {
    margin-bottom: 5px;
    color: #536887;
    font-size: 11px;
    font-weight: 600;
}

.form-control,
.form-select {
    height: 41px;
    background: #f2f5fa;
    border: 1px solid #ced8e7;
    border-radius: 10px;
    color: #263c5d;
    font-size: 12px;
}

.form-control:focus,
.form-select:focus {
    background: #ffffff;
    border-color: #76a6ed;
    box-shadow: 0 0 0 3px rgba(47, 116, 217, 0.1);
}

.active-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 55px;
}

.active-title {
    font-size: 13px;
    font-weight: 600;
    color: #192e4e;
}

.active-subtitle {
    margin-top: 2px;
    color: #99a6ba;
    font-size: 10px;
}

/* toggle */

.switch {
    position: relative;
    width: 40px;
    height: 23px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    inset: 0;
    cursor: pointer;
    background: #bfc9d7;
    border-radius: 30px;
    transition: 0.2s;
}

.slider::before {
    content: '';
    position: absolute;
    width: 19px;
    height: 19px;
    left: 2px;
    top: 2px;
    background: #ffffff;
    border-radius: 50%;
    transition: 0.2s;
}

.switch input:checked + .slider {
    background: #347ae0;
}

.switch input:checked + .slider::before {
    transform: translateX(17px);
}

.save-product-btn {
    margin-top: 16px;
    width: 100%;
    min-height: 44px;
    border: 0;
    border-radius: 24px;
    background: #193b6b;
    color: #ffffff;
    font-size: 11px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.save-product-btn:disabled {
    opacity: 0.7;
}
</style>