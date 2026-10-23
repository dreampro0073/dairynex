<script setup>
import { Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import PlatformLayout from '@/Layouts/PlatformLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import LoginDetails from '@/Components/LoginDetails.vue'

const props = defineProps({
    client: Object,
    admins: Array,
    subscriptions: Object,
    plans: Array,
})

const loginOpen = ref(false),
    editing = ref(null),
    adminOpen = ref(false)

const admin = useForm({
    name: '',
    mobile: '',
    email: '',
    status: 1,
})

const subscription = useForm({
    action: props.client.current_subscription ? 'renew' : 'assign',
    plan_id: props.client.current_subscription?.plan_id || '',
    months: 1,
})

const editAdmin = (value) => {
    editing.value = value?.id || null
    admin.defaults({
        name: value?.name || '',
        mobile: value?.mobile || '',
        email: value?.email || '',
        status: value?.status ?? 1,
    })
    admin.reset()
    admin.clearErrors()
    adminOpen.value = true
}

const saveAdmin = () => {
    const options = {
        onSuccess: () => {
            adminOpen.value = false
            admin.reset()
        },
    }
    editing.value
        ? admin.put(`/platform/clients/${props.client.id}/admins/${editing.value}`, options)
        : admin.post(`/platform/clients/${props.client.id}/admins`, options)
}

const saveSubscription = () => {
    if (
        window.confirm(
            subscription.action === 'expire'
                ? 'Expire all current and scheduled subscriptions? Existing invoices remain payable.'
                : 'Apply this subscription? A full-price invoice will be created for the selected period.',
        )
    )
        subscription.post(`/platform/clients/${props.client.id}/subscription`)
}

const toggleDairy = () => {
    if (window.confirm('Change dairy status?'))
        router.put(`/platform/clients/${props.client.id}/status`, {
            status: props.client.status === 1 ? 0 : 1,
        })
}

const status = (value) =>
    value.status === 2
        ? 'Replaced'
        : value.status === 0 ||
            (value.end_date &&
                value.end_date <
                    new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Kolkata' }))
          ? 'Expired'
          : value.start_date > new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Kolkata' })
            ? 'Scheduled'
            : 'Active'
</script>

<template>
    <PlatformLayout
        :title="client.name"
        :meta="`Dairy #${client.id} · ${client.status === 1 ? 'Active' : 'Inactive'}`"
    >
        <template #actions>
            <Link
                :href="`/platform/clients/${client.id}/edit`"
                class="btn secondary sm"
            >
                Edit Dairy
            </Link>
            <button
                class="btn sm"
                @click="loginOpen = true"
            >
                View Login Details
            </button>
        </template>
        <Link
            href="/platform/clients"
            class="link"
        >
            ← Back to Clients
        </Link>
        <section class="panel section-space">
            <div class="panel-head">
                <h3>Dairy Details</h3>
                <button
                    class="btn secondary sm"
                    @click="toggleDairy"
                >
                    {{ client.status === 1 ? 'Deactivate' : 'Activate' }}
                </button>
            </div>
            <dl class="detail-grid">
                <div
                    v-for="(value, label) in {
                        'Business Name': client.name,
                        'Legal Name': client.legal_name,
                        Email: client.email,
                        Mobile: client.mobile,
                        Location: [client.city, client.state, client.pincode]
                            .filter(Boolean)
                            .join(', '),
                        Address: client.address,
                        Customers: client.customers_count,
                        'Delivery Boys': client.delivery_boys_count,
                        'Current Plan': client.current_subscription?.plan?.name,
                    }"
                    :key="label"
                >
                    <dt>{{ label }}</dt>
                    <dd>{{ value ?? '—' }}</dd>
                </div>
            </dl>
        </section>
        <section class="panel section-space">
            <div class="panel-head">
                <h3>Client Admin Accounts</h3>
                <button
                    class="btn secondary sm"
                    @click="editAdmin(null)"
                >
                    + Add Admin
                </button>
            </div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email / Mobile</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="user in admins"
                            :key="user.id"
                        >
                            <td>{{ user.name }}</td>
                            <td>
                                {{ user.email || '—' }}
                                <div class="muted">{{ user.mobile }}</div>
                            </td>
                            <td>{{ user.status === 1 ? 'Active' : 'Inactive' }}</td>
                            <td>{{ user.last_login_at || 'Never' }}</td>
                            <td>
                                <button
                                    class="btn secondary sm"
                                    @click="editAdmin(user)"
                                >
                                    Edit account
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <form
                v-if="adminOpen"
                @submit.prevent="saveAdmin"
            >
                <div class="form-grid">
                    <div
                        v-for="[key, label] in [
                            ['name', 'Name'],
                            ['mobile', 'Login Mobile'],
                            ['email', 'Login Email'],
                        ]"
                        :key="key"
                        class="field"
                    >
                        <label :for="`admin-${key}`">{{ label }}</label>
                        <input
                            :id="`admin-${key}`"
                            v-model="admin[key]"
                            :type="key === 'email' ? 'email' : 'text'"
                            :required="key !== 'email'"
                            :maxlength="key === 'mobile' ? 20 : 150"
                        />
                    </div>
                    <div class="field">
                        <label for="admin-status">Status</label>
                        <select
                            id="admin-status"
                            v-model="admin.status"
                        >
                            <option :value="1">Active</option>
                            <option :value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button
                        class="btn sm"
                        :disabled="admin.processing"
                    >
                        Save Admin
                    </button>
                    <button
                        type="button"
                        class="btn secondary sm"
                        @click="adminOpen = false"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </section>
        <section class="panel section-space">
            <div class="panel-head">
                <h3>Manage Subscription</h3>
            </div>
            <form @submit.prevent="saveSubscription">
                <div class="form-grid">
                    <div class="field">
                        <label for="action">Action</label>
                        <select
                            id="action"
                            v-model="subscription.action"
                        >
                            <option
                                v-for="action in [
                                    'assign',
                                    'renew',
                                    'upgrade',
                                    'downgrade',
                                    'expire',
                                ]"
                                :key="action"
                                :value="action"
                            >
                                {{ action }}
                            </option>
                        </select>
                    </div>
                    <div
                        v-if="subscription.action !== 'expire'"
                        class="field"
                    >
                        <label for="subscription-plan">Plan</label>
                        <select
                            id="subscription-plan"
                            v-model="subscription.plan_id"
                            required
                        >
                            <option value="">Choose plan</option>
                            <option
                                v-for="plan in plans"
                                :key="plan.id"
                                :value="plan.id"
                            >
                                {{ plan.name }} — ₹{{ plan.monthly_price }}/mo
                            </option>
                        </select>
                    </div>
                    <div
                        v-if="subscription.action !== 'expire'"
                        class="field"
                    >
                        <label for="months">Months</label>
                        <input
                            id="months"
                            v-model="subscription.months"
                            type="number"
                            min="1"
                            max="36"
                            required
                        />
                    </div>
                    <p class="muted full">
                        Renewals begin after the current period. Upgrades and downgrades begin today
                        and create a full-price invoice; previous invoices remain payable. Ended
                        periods expire automatically by date.
                    </p>
                </div>
                <div class="form-actions">
                    <button
                        class="btn sm"
                        :disabled="subscription.processing"
                    >
                        Apply Subscription
                    </button>
                    <Link
                        href="/platform/billing"
                        class="btn secondary sm"
                    >
                        View Billing
                    </Link>
                </div>
            </form>
        </section>
        <section class="panel section-space">
            <div class="panel-head">
                <h3>Subscription History</h3>
            </div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Monthly Price</th>
                            <th>From</th>
                            <th>Through</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="s in subscriptions.data"
                            :key="s.id"
                        >
                            <td>{{ s.plan?.name || 'Unavailable plan' }}</td>
                            <td>₹{{ s.monthly_price ?? s.plan?.monthly_price }}</td>
                            <td>{{ s.start_date }}</td>
                            <td>{{ s.end_date || 'Open ended' }}</td>
                            <td>{{ status(s) }}</td>
                        </tr>
                        <tr v-if="!subscriptions.data.length">
                            <td
                                colspan="5"
                                class="empty"
                            >
                                No subscription history.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :data="subscriptions" />
        </section>
        <LoginDetails
            v-if="loginOpen"
            :client-id="client.id"
            @close="loginOpen = false"
        />
    </PlatformLayout>
</template>
