<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import PlatformLayout from '@/Layouts/PlatformLayout.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
    clients: Object,
    filters: Object,
    health: Object,
})

const form = useForm({ search: props.filters.search || '' })
</script>

<template>
    <PlatformLayout
        title="Client Databases"
        meta="Tenant data and database monitoring"
    >
        <template #actions>
            <Link
                href="/platform/databases"
                class="btn secondary sm"
            >
                Refresh Status
            </Link>
        </template>
        <section class="panel">
            <div class="panel-head">
                <h3>Database Health</h3>
                <span class="badge ok">{{ health.connected ? 'Connected' : 'Unavailable' }}</span>
            </div>
            <dl class="detail-grid">
                <div>
                    <dt>Engine</dt>
                    <dd>{{ health.driver }}</dd>
                </div>
                <div>
                    <dt>Architecture</dt>
                    <dd>{{ health.tenancy }}</dd>
                </div>
                <div>
                    <dt>Backups</dt>
                    <dd>{{ health.backup_status }}</dd>
                </div>
                <div>
                    <dt>Storage reporting</dt>
                    <dd>Per-dairy disk usage is unavailable in a shared database.</dd>
                </div>
            </dl>
            <form
                class="filters"
                @submit.prevent="form.get('/platform/databases', { preserveState: true })"
            >
                <label>
                    Search
                    <input
                        v-model="form.search"
                        placeholder="Dairy name"
                    />
                </label>
                <button class="btn sm">Search</button>
                <Link
                    href="/platform/databases"
                    class="btn secondary sm"
                >
                    Clear
                </Link>
            </form>
        </section>
        <div class="cards-grid section-space">
            <article
                v-for="client in clients.data"
                :key="client.id"
                class="info-card"
            >
                <div class="h">{{ client.name }}</div>
                <div class="s">
                    Tenant #{{ client.id }} · {{ client.customers_count }} customers ·
                    {{ client.delivery_boys_count }} delivery boys
                </div>
                <div>
                    <span
                        class="badge"
                        :class="client.status === 1 ? 'ok' : 'due'"
                    >
                        {{ client.status === 1 ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <Link
                    :href="`/platform/clients/${client.id}`"
                    class="link"
                >
                    View Tenant Details →
                </Link>
            </article>
        </div>
        <div
            v-if="!clients.data.length"
            class="empty"
        >
            No dairies found.
        </div>
        <Pagination :data="clients" />
    </PlatformLayout>
</template>
