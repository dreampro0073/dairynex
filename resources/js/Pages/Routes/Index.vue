<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    routes: {
        type: Array,
        default: () => [],
    },

    summary: {
        type: Object,
        default: () => ({
            routes_count: 0,
            stops_count: 0,
        }),
    },
})

const deleteRoute = (route) => {
    if (
        !confirm(
            `Are you sure you want to delete "${route.name}"?`
        )
    ) {
        return
    }

    router.delete(
        `/routes/${route.id}`,
        {
            preserveScroll: true,
        }
    )
}
</script>

<template>
    <AdminLayout>
        <!-- Page Header -->
        <template #title>
            Routes
        </template>

        <template #subtitle>
            {{ summary.routes_count }}
            routes covering
            {{
                Number(
                    summary.stops_count || 0
                ).toLocaleString('en-IN')
            }}
            stops
        </template>

        <template #header-action>
            <Link
                href="/routes/create"
                class="btn secondary sm"
            >
                <i class="bi bi-plus-lg"></i>
                Add Route
            </Link>
        </template>

        <!-- Routes -->
        <div class="panel">
            <div class="panel-head">
                <h3>
                    Delivery Routes
                </h3>

                <Link
                    href="/routes/create"
                    class="link"
                >
                    + Add Route
                </Link>
            </div>

            <div
                v-if="routes.length"
                class="cards-grid"
                style="padding: 15px;"
            >
                <div
                    v-for="route in routes"
                    :key="route.id"
                    class="info-card"
                >
                    <!-- Route Header -->
                    <div
                        style="
                            display: flex;
                            align-items: flex-start;
                            justify-content: space-between;
                            gap: 10px;
                        "
                    >
                        <div>
                            <div class="h">
                                {{ route.name }}
                            </div>

                            <div class="s">
                                {{
                                    route.area_locality || '-'
                                }}

                                ·

                                {{
                                    route.customers_count
                                }}
                                stops

                                ·

                                {{
                                    route.delivery_boy
                                }}
                            </div>
                        </div>

                        <div
                            style="
                                flex-shrink: 0;
                                white-space: nowrap;
                            "
                        >
                            <Link
                                :href="
                                    `/routes/${route.id}/edit`
                                "
                                class="link"
                            >
                                Edit
                            </Link>

                            &nbsp;·&nbsp;

                            <button
                                type="button"
                                class="link-action danger"
                                @click="
                                    deleteRoute(route)
                                "
                            >
                                Delete
                            </button>
                        </div>
                    </div>

                    <!-- Progress -->
                    <div class="progress">
                        <div
                            :style="{
                                width:
                                    `${route.delivered_percentage}%`
                            }"
                        ></div>
                    </div>

                    <div
                        class="s"
                        style="margin-bottom: 0;"
                    >
                        {{
                            route.delivered_percentage
                        }}% delivered today
                    </div>
                </div>
            </div>

            <!-- Empty -->
            <div
                v-else
                class="empty-state"
            >
                <div class="ic">
                    <i class="bi bi-signpost"></i>
                </div>

                <div class="t">
                    No delivery routes found
                </div>

                <div class="s">
                    Create your first delivery route.
                </div>

                <Link
                    href="/routes/create"
                    class="btn sm"
                >
                    <i class="bi bi-plus-lg"></i>
                    Add Route
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>