<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, router } from '@inertiajs/vue3'

defineProps({
    products: {
        type: Array,
        default: () => [],
    },
})

const units = {
    1: 'Litre',
    2: 'ml',
    3: 'kg',
    4: 'g',
    5: 'Piece',
    6: 'Packet',
}

const unitText = (unit) => {
    return units[Number(unit)] ?? '-'
}

const statusText = (status) => {
    status = Number(status)

    if (status === 1) return 'Active'
    if (status === 2) return 'Seasonal'

    return 'Inactive'
}

const statusClass = (status) => {
    status = Number(status)

    if (status === 1) return 'ok'
    if (status === 2) return 'pending'

    return 'neutral'
}

const deleteProduct = (product) => {
    if (!confirm(`Are you sure you want to delete "${product.name}"?`)) {
        return
    }

    router.delete(`/products/${product.id}`, {
        preserveScroll: true,
    })
}
</script>

<template>
    <AdminLayout>
        <!-- Page title -->
        <template #title>
            Products
        </template>

        <template #subtitle>
            {{ products.length }} products
        </template>

        <!-- Top right button -->
        <template #header-action>
            <Link
                href="/products/create"
                class="btn secondary sm"
            >
                <i class="bi bi-plus-lg"></i>
                Add Product
            </Link>
        </template>

        <!-- Products Panel -->
        <div class="panel">

            <!-- Panel Header -->
            <div class="panel-head">
                <h3>
                    Products
                </h3>

                <Link
                    href="/products/create"
                    class="link"
                >
                    + Add Product
                </Link>
            </div>

            <!-- Table -->
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>SKU</th>
                            <th>Unit</th>
                            <th class="num">
                                Current Rate
                            </th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="product in products"
                            :key="product.id"
                        >
                            <!-- Product -->
                            <td>
                                <div class="client-row-name">
                                    {{ product.name }}
                                </div>
                            </td>

                            
                            <td>
                                {{ product.category }}
                            </td>

                            <!-- SKU -->
                            <td>
                                {{ product.sku || '-' }}
                            </td>

                            <!-- Unit -->
                            <td>
                                {{ unitText(product.unit_type) }}
                            </td>

                            <!-- Rate -->
                            <td class="num">
                                ₹{{
                                    Number(
                                        product.base_rate || 0
                                    ).toFixed(2)
                                }}
                            </td>

                            <!-- Status -->
                            <td>
                                <span
                                    class="badge"
                                    :class="statusClass(product.status)"
                                >
                                    {{ statusText(product.status) }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td>
                                <Link
                                    :href="`/products/${product.id}/edit`"
                                    class="link"
                                >
                                    Edit
                                </Link>

                                &nbsp;·&nbsp;

                                <button
                                    type="button"
                                    class="link-action danger"
                                    @click="deleteProduct(product)"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="products.length === 0">
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="ic">
                                        <i class="bi bi-box"></i>
                                    </div>

                                    <div class="t">
                                        No products found
                                    </div>

                                    <div class="s">
                                        Add your first product to start your catalogue.
                                    </div>

                                    <Link
                                        href="/products/create"
                                        class="btn sm"
                                    >
                                        <i class="bi bi-plus-lg"></i>
                                        Add Product
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
