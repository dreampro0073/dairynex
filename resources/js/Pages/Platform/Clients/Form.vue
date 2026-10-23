<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import PlatformLayout from '@/Layouts/PlatformLayout.vue'

const props = defineProps({
    client: Object,
    plans: Array,
})

const fields = [
    ['name', 'Business Name'],
    ['legal_name', 'Legal Name'],
    ['mobile', 'Business Phone'],
    ['email', 'Business Email'],
    ['city', 'City'],
    ['state', 'State'],
    ['pincode', 'Pincode'],
]

const form = useForm(
    Object.fromEntries([
        ...fields.map(([key]) => [key, props.client?.[key] || '']),
        ['address', props.client?.address || ''],
        ['status', props.client?.status ?? 1],
        ['admin_name', ''],
        ['admin_mobile', ''],
        ['admin_email', ''],
        ['plan_id', ''],
    ]),
)

const save = () =>
    props.client ? form.put(`/platform/clients/${props.client.id}`) : form.post('/platform/clients')
</script>

<template>
    <PlatformLayout :title="client ? 'Edit Client' : 'Add New Client'">
        <template #actions>
            <Link
                href="/platform/clients"
                class="btn secondary sm"
            >
                ← Back to Clients
            </Link>
        </template>
        <form @submit.prevent="save">
            <section class="panel">
                <div class="panel-head">
                    <h3>{{ client ? `Edit Client — ${client.name}` : 'Add New Client' }}</h3>
                </div>
                <div class="form-grid">
                    <div
                        v-for="[key, label] in fields"
                        :key="key"
                        class="field"
                    >
                        <label :for="key">{{ label }}</label>
                        <input
                            :id="key"
                            v-model="form[key]"
                            :type="key === 'email' ? 'email' : 'text'"
                            :required="key === 'name'"
                            :maxlength="
                                ['mobile', 'pincode'].includes(key)
                                    ? 20
                                    : key === 'legal_name'
                                      ? 200
                                      : ['city', 'state'].includes(key)
                                        ? 100
                                        : 150
                            "
                        />
                        <span class="error-text">{{ form.errors[key] }}</span>
                    </div>
                    <div class="field">
                        <label for="status">Status</label>
                        <select
                            id="status"
                            v-model="form.status"
                        >
                            <option :value="1">Active</option>
                            <option :value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="field full">
                        <label for="address">Address</label>
                        <textarea
                            id="address"
                            v-model="form.address"
                            maxlength="2000"
                        ></textarea>
                    </div>
                </div>
            </section>
            <section
                v-if="!client"
                class="panel section-space"
            >
                <div class="panel-head">
                    <h3>Client Admin & Plan</h3>
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label for="owner">Owner / Client Admin Name</label>
                        <input
                            id="owner"
                            v-model="form.admin_name"
                            required
                            maxlength="150"
                        />
                    </div>
                    <div class="field">
                        <label for="admin-mobile">Admin Login Mobile</label>
                        <input
                            id="admin-mobile"
                            v-model="form.admin_mobile"
                            required
                            pattern="[0-9]{7,20}"
                            maxlength="20"
                        />
                    </div>
                    <div class="field">
                        <label for="admin-email">Admin Login Email</label>
                        <input
                            id="admin-email"
                            v-model="form.admin_email"
                            type="email"
                            maxlength="150"
                        />
                    </div>
                    <div class="field">
                        <label for="plan">Initial Plan (one month)</label>
                        <select
                            id="plan"
                            v-model="form.plan_id"
                        >
                            <option value="">Assign later</option>
                            <option
                                v-for="plan in plans"
                                :key="plan.id"
                                :value="plan.id"
                            >
                                {{ plan.name }} — ₹{{ plan.monthly_price }}/mo
                            </option>
                        </select>
                    </div>
                    <p class="muted full">
                        Generate the Client Admin’s temporary password after saving this dairy.
                        Assigning a plan creates a SaaS invoice.
                    </p>
                </div>
            </section>
            <div class="action-row section-space">
                <button
                    class="btn pill"
                    :disabled="form.processing"
                >
                    {{ client ? 'Update Client' : 'Save Client' }}
                </button>
                <Link
                    href="/platform/clients"
                    class="btn secondary pill"
                >
                    Cancel
                </Link>
            </div>
        </form>
    </PlatformLayout>
</template>
