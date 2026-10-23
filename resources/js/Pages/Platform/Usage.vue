<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import PlatformLayout from '@/Layouts/PlatformLayout.vue'
import PlatformStats from '@/Components/PlatformStats.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
    clients: Object,
    filters: Object,
    stats: Object,
})

const filter = useForm({
    search: props.filters.search || '',
    from: props.filters.from,
    to: props.filters.to,
})

const exportUrl = computed(
    () =>
        '/platform/usage/export?' +
        new URLSearchParams(Object.entries(props.filters).filter(([, v]) => v)).toString(),
)

const completion = (client) =>
    client.deliveries ? Math.round((client.completed / client.deliveries) * 100) : 0
</script>

<template>
    <PlatformLayout
        title="Usage Statistics"
        meta="Actual activity and platform reports"
    >
        <template #actions>
            <a
                :href="exportUrl"
                class="btn secondary sm"
            >
                Export CSV
            </a>
        </template>
        <PlatformStats :stats="stats" />
        <section class="panel">
            <div class="panel-head">
                <h3>Top Clients by Activity</h3>
            </div>
            <form
                class="filters"
                @submit.prevent="filter.get('/platform/usage', { preserveState: true })"
            >
                <label>
                    Dairy
                    <input
                        v-model="filter.search"
                        placeholder="Search dairy"
                    />
                </label>
                <label>
                    From
                    <input
                        v-model="filter.from"
                        type="date"
                        required
                    />
                </label>
                <label>
                    To
                    <input
                        v-model="filter.to"
                        type="date"
                        required
                    />
                </label>
                <button class="btn sm">Run Report</button>
                <Link
                    href="/platform/usage"
                    class="btn secondary sm"
                >
                    Today
                </Link>
            </form>
            <div
                v-for="client in clients.data"
                :key="client.id"
                class="bar-row"
            >
                <Link
                    :href="`/platform/clients/${client.id}`"
                    class="bl link"
                >
                    {{ client.name }}
                </Link>
                <div
                    class="bar-track"
                    role="progressbar"
                    :aria-label="`${client.name} completed deliveries`"
                    :aria-valuenow="completion(client)"
                    aria-valuemin="0"
                    aria-valuemax="100"
                >
                    <div
                        class="bar-fill"
                        :style="{ width: `${completion(client)}%` }"
                    ></div>
                </div>
                <div class="bv">{{ completion(client) }}%</div>
                <span class="muted">
                    {{ client.completed }} / {{ client.deliveries }} completed
                </span>
            </div>
            <div
                v-if="!clients.data.length"
                class="empty"
            >
                No dairies found.
            </div>
            <Pagination :data="clients" />
        </section>
    </PlatformLayout>
</template>
