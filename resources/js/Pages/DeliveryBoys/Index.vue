<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
    deliveryBoys: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({ total: 0, active: 0, inactive: 0 }) },
})

const search = ref('')

const status = ref('')

const visibleBoys = computed(() =>
    props.deliveryBoys.filter((boy) => {
        const query = search.value.trim().toLowerCase()
        return (
            [boy.name, boy.mobile, boy.email, boy.route].some((value) =>
                String(value ?? '')
                    .toLowerCase()
                    .includes(query),
            ) &&
            (status.value === '' || Number(boy.status) === Number(status.value))
        )
    }),
)

const remove = (boy) => {
    if (confirm(`Delete "${boy.name}"? Their routes will become unassigned.`)) {
        router.delete(`/delivery-boys/${boy.id}`, { preserveScroll: true })
    }
}
</script>

<template>
    <Head title="Delivery Boys" />
    <AdminLayout>
        <template #title>Delivery Boys</template>
        <template #subtitle>Manage delivery staff</template>
        <template #header-action>
            <Link
                href="/delivery-boys/create"
                class="btn secondary sm"
            >
                + Add Delivery Boy
            </Link>
        </template>
        <div class="stat-row">
            <div class="stat">
                <div class="label">Total Delivery Boys</div>
                <div class="value">{{ stats.total }}</div>
            </div>
            <div class="stat blue">
                <div class="label">Active</div>
                <div class="value">{{ stats.active }}</div>
            </div>
            <div class="stat butter">
                <div class="label">Inactive</div>
                <div class="value">{{ stats.inactive }}</div>
            </div>
        </div>
        <div class="panel">
            <div class="panel-head">
                <h3>All Delivery Boys</h3>
            </div>
            <div class="form-grid">
                <div class="field">
                    <label for="boy-search">Search staff or route</label>
                    <input
                        id="boy-search"
                        v-model="search"
                        type="search"
                        placeholder="Name, mobile, email or route"
                    />
                </div>
                <div class="field">
                    <label for="status-filter">Status</label>
                    <select
                        id="status-filter"
                        v-model="status"
                    >
                        <option value="">All statuses</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>Route</th>
                            <th class="num">Expected Stops</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="boy in visibleBoys"
                            :key="boy.id"
                        >
                            <td>{{ boy.name }}</td>
                            <td>{{ boy.mobile }}</td>
                            <td>
                                <span class="route-tag">{{ boy.route }}</span>
                            </td>
                            <td class="num">{{ boy.expected_stops }}</td>
                            <td>
                                <span
                                    class="badge"
                                    :class="boy.status === 1 ? 'ok' : 'due'"
                                >
                                    {{ boy.status === 1 ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <Link
                                    :href="`/delivery-boys/${boy.id}/edit`"
                                    class="btn secondary sm"
                                >
                                    Edit
                                </Link>
                                <button
                                    type="button"
                                    class="btn secondary sm"
                                    @click="remove(boy)"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!visibleBoys.length">
                            <td colspan="6">
                                <div class="empty-state">No delivery boys match your search.</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
