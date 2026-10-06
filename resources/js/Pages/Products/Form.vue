<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    product: {
        type: Object,
        default: null,
    },

    categories: {
        type: Array,
        default: () => [],
    },
})

const isEdit = computed(() => Boolean(props.product))

const form = useForm({
    name: props.product?.name ?? '',
    category_id: props.product?.category_id ?? '',
    sku: props.product?.sku ?? '',
    unit_type: Number(props.product?.unit_type ?? 1),
    base_rate: props.product?.base_rate ?? '',
    status: Number(props.product?.status ?? 1),
})

const submit = () => {
    if (isEdit.value) {
        form.put(`/products/${props.product.id}`)

        return
    }

    form.post('/products')
}
</script>

<template>
    <AdminLayout>
        <template #title>
            {{ isEdit ? 'Edit Product' : 'Add New Product' }}
        </template>

        <template #subtitle>
            {{
                isEdit
                    ? 'Update product information'
                    : 'Add a product to your catalogue'
            }}
        </template>

        <Link
            href="/products"
            class="btn secondary sm pill"
            style="margin-bottom: 14px"
        >
            ← Back to Products & Rates
        </Link>

        <form @submit.prevent="submit">
            <div class="panel">
                <div class="panel-head">
                    <h3>
                        {{ isEdit ? 'Edit Product' : 'Add New Product' }}
                    </h3>
                </div>

                <div class="form-grid">
                    <!-- Name -->
                    <div class="field full">
                        <label>
                            Product Name
                        </label>

                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Cow Ghee"
                            :class="{
                                'field-error': form.errors.name
                            }"
                        >

                        <div
                            v-if="form.errors.name"
                            class="error-text"
                        >
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <!-- Category -->
                    <div class="field">
                        <label>
                            Category
                        </label>

                        <select
                            v-model="form.category_id"
                            :class="{
                                'field-error': form.errors.category_id
                            }"
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

                        <div
                            v-if="form.errors.category_id"
                            class="error-text"
                        >
                            {{ form.errors.category_id }}
                        </div>
                    </div>

                    <!-- SKU -->
                    <div class="field">
                        <label>
                            SKU
                        </label>

                        <input
                            v-model="form.sku"
                            type="text"
                            placeholder="e.g. MLK-FC1"
                            :class="{
                                'field-error': form.errors.sku
                            }"
                        >

                        <div
                            v-if="form.errors.sku"
                            class="error-text"
                        >
                            {{ form.errors.sku }}
                        </div>
                    </div>

                    <!-- Unit -->
                    <div class="field">
                        <label>
                            Unit
                        </label>

                        <select
                            v-model.number="form.unit_type"
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

                    <!-- Rate -->
                    <div class="field">
                        <label>
                            Rate (₹)
                        </label>

                        <input
                            v-model="form.base_rate"
                            type="number"
                            min="0"
                            step="0.01"
                            placeholder="e.g. 60"
                            :class="{
                                'field-error': form.errors.base_rate
                            }"
                        >

                        <div
                            v-if="form.errors.base_rate"
                            class="error-text"
                        >
                            {{ form.errors.base_rate }}
                        </div>
                    </div>
                </div>

                <!-- Active -->
                <div class="toggle-row">
                    <div>
                        <div class="t-h">
                            Active
                        </div>

                        <div class="t-s">
                            Available for customer selection
                        </div>
                    </div>

                    <button
                        type="button"
                        class="switch"
                        :class="{
                            on: Number(form.status) === 1
                        }"
                        @click="
                            form.status =
                                Number(form.status) === 1 ? 0 : 1
                        "
                    ></button>
                </div>
            </div>

            <button
                type="submit"
                class="btn pill full-btn"
                :disabled="form.processing"
            >
                <i class="bi bi-floppy"></i>

                {{
                    form.processing
                        ? 'Saving...'
                        : isEdit
                            ? 'Update Product'
                            : 'Save Product'
                }}
            </button>
        </form>
    </AdminLayout>
</template>