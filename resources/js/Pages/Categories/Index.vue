<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
})

const showForm = ref(false)
const editingCategory = ref(null)

const form = useForm({
    name: '',
    status: 1,
})

const openAdd = () => {
    editingCategory.value = null

    form.reset()
    form.clearErrors()

    form.status = 1
    showForm.value = true
}

const openEdit = (category) => {
    editingCategory.value = category

    form.clearErrors()

    form.name = category.name
    form.status = Number(category.status)

    showForm.value = true
}

const closeForm = () => {
    showForm.value = false
    editingCategory.value = null

    form.reset()
    form.clearErrors()
}

const submit = () => {
    if (editingCategory.value) {
        form.put(
            `/categories/${editingCategory.value.id}`,
            {
                preserveScroll: true,
                onSuccess: closeForm,
            }
        )

        return
    }

    form.post('/categories', {
        preserveScroll: true,
        onSuccess: closeForm,
    })
}

const deleteCategory = (category) => {
    if (!confirm(`Delete "${category.name}"?`)) {
        return
    }

    router.delete(`/categories/${category.id}`, {
        preserveScroll: true,
    })
}

const statusLabel = (status) => {
    if (Number(status) === 1) return 'Active'
    if (Number(status) === 2) return 'Seasonal'

    return 'Inactive'
}

const statusClass = (status) => {
    if (Number(status) === 1) return 'ok'
    if (Number(status) === 2) return 'pending'

    return 'neutral'
}
</script>

<template>
    <AdminLayout>
        <template #title>
            Product Categories
        </template>

        <template #subtitle>
            {{ categories.length }} categories
        </template>

        <template #header-action>
            <button
                type="button"
                class="btn secondary sm"
                @click="openAdd"
            >
                + Add Category
            </button>
        </template>

        <!-- List -->
        <div
            v-if="!showForm"
            class="panel"
        >
            <div class="panel-head">
                <h3>
                    Product Categories
                </h3>

                <button
                    type="button"
                    class="link-action"
                    @click="openAdd"
                >
                    + Add Category
                </button>
            </div>

            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Category</th>

                            <th class="num">
                                Products
                            </th>

                            <th>Status</th>

                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="category in categories"
                            :key="category.id"
                        >
                            <td>
                                {{ category.name }}
                            </td>

                            <td class="num">
                                {{ category.products_count ?? 0 }}
                            </td>

                            <td>
                                <span
                                    class="badge"
                                    :class="statusClass(category.status)"
                                >
                                    {{ statusLabel(category.status) }}
                                </span>
                            </td>

                            <td>
                                <button
                                    type="button"
                                    class="link-action"
                                    @click="openEdit(category)"
                                >
                                    Edit
                                </button>

                                &nbsp;·&nbsp;

                                <button
                                    type="button"
                                    class="link-action danger"
                                    @click="deleteCategory(category)"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>

                        <tr v-if="categories.length === 0">
                            <td colspan="4">
                                <div class="empty-state">
                                    <div class="ic">
                                        <i class="bi bi-grid"></i>
                                    </div>

                                    <div class="t">
                                        No categories found
                                    </div>

                                    <div class="s">
                                        Add your first product category.
                                    </div>

                                    <button
                                        type="button"
                                        class="btn sm"
                                        @click="openAdd"
                                    >
                                        Add Category
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add/Edit -->
        <form
            v-else
            @submit.prevent="submit"
        >
            <button
                type="button"
                class="btn secondary sm pill"
                style="margin-bottom: 14px"
                @click="closeForm"
            >
                ← Back to Categories
            </button>

            <div class="panel">
                <div class="panel-head">
                    <h3>
                        {{
                            editingCategory
                                ? 'Edit Category'
                                : 'Add Category'
                        }}
                    </h3>
                </div>

                <div class="form-grid">
                    <div class="field full">
                        <label>
                            Category Name
                        </label>

                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Milk"
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

                    <div class="field full">
                        <label>
                            Status
                        </label>

                        <select v-model.number="form.status">
                            <option :value="1">
                                Active
                            </option>

                            <option :value="2">
                                Seasonal
                            </option>

                            <option :value="0">
                                Inactive
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <button
                type="submit"
                class="btn pill full-btn"
                :disabled="form.processing"
            >
                {{
                    editingCategory
                        ? 'Update Category'
                        : 'Save Category'
                }}
            </button>
        </form>
    </AdminLayout>
</template>