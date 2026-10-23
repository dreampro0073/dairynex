<script setup>
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'

defineProps({ changes: Object })

const page = usePage()

const decision = useForm({ status: 2 })

const decide = (id, status) => {
    decision.status = status
    decision.put(`/quantity-change-requests/${id}`)
}
</script>

<template>
    <Head title="Quantity Changes" />
    <WorkspaceLayout title="Quantity Change Requests">
        <div class="panel-head">
            <Link
                href="/subscriptions"
                class="link"
            >
                Subscriptions
            </Link>
        </div>
        <div
            v-if="Object.keys(decision.errors).length"
            class="alert alert-danger"
            role="alert"
        >
            {{ Object.values(decision.errors).join(' ') }}
        </div>
        <div class="panel">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Subscription</th>
                            <th>Quantity</th>
                            <th>Effective from</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="change in changes.data"
                            :key="change.id"
                        >
                            <td>{{ change.subscription_no }}</td>
                            <td>{{ change.old_quantity }} → {{ change.requested_quantity }}</td>
                            <td>{{ change.effective_from }}</td>
                            <td>{{ change.reason }}</td>
                            <td>
                                {{
                                    { 1: 'Pending', 2: 'Approved', 3: 'Rejected', 4: 'Cancelled' }[
                                        change.status
                                    ]
                                }}
                            </td>
                            <td
                                v-if="
                                    page.props.auth?.user?.role === 'client_admin' &&
                                    change.status === 1
                                "
                            >
                                <button
                                    class="btn sm"
                                    :disabled="decision.processing"
                                    @click="decide(change.id, 2)"
                                >
                                    Approve
                                </button>
                                <button
                                    class="btn secondary sm"
                                    :disabled="decision.processing"
                                    @click="decide(change.id, 3)"
                                >
                                    Reject
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!changes.data.length">
                            <td colspan="6">
                                <div class="empty-state">No quantity change requests.</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :data="changes" />
        </div>
    </WorkspaceLayout>
</template>
