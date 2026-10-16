<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

const page = usePage()

const sidebarOpen = ref(false)

const currentUrl = computed(() => page.url)
watch(currentUrl, () => { sidebarOpen.value = false })
const userInitials = computed(() => (page.props.auth?.user?.name ?? 'DN').split(' ').map(part => part[0]).slice(0, 2).join(''))

const isActive = (path) => {
    return currentUrl.value.startsWith(path)
}

const closeSidebar = () => {
    sidebarOpen.value = false
}
</script>

<template>
    <div class="shell-wrapper">
        <div class="shell">
            <!-- Mobile overlay -->
            <div
                class="overlay"
                :class="{ show: sidebarOpen }"
                @click="closeSidebar"
            ></div>

            <!-- Sidebar -->
            <aside
                class="sidebar"
                :class="{ open: sidebarOpen }"
            >
                <div class="sidebar-brand">
                    <div class="logo">
                        {{ userInitials }}
                    </div>

                    <div>
                        <div class="name">
                            {{ page.props.auth?.client?.name ?? 'DairyNex' }}
                        </div>

                        <div class="sub">
                            {{ page.props.auth?.client?.city ?? 'Dairy management' }}
                        </div>
                    </div>
                </div>

                <div class="nav-group">
                    <Link
                        href="/dashboard"
                        class="nav-item"
                        :class="{ current: isActive('/dashboard') }"
                    >
                        <span class="ic">
                            <i class="bi bi-grid"></i>
                        </span>

                        Dashboard
                    </Link>

                    <Link
                        href="/customers"
                        class="nav-item"
                        :class="{ current: isActive('/customers') }"
                    >
                        <span class="ic">
                            <i class="bi bi-people"></i>
                        </span>

                        Customers
                    </Link>

                    <Link
                        href="/products"
                        class="nav-item"
                        :class="{
                            current:
                                isActive('/products') &&
                                !isActive('/categories')
                        }"
                    >
                        <span class="ic">
                            <i class="bi bi-box"></i>
                        </span>

                        Products
                    </Link>

                    <Link
                        href="/categories"
                        class="nav-item"
                        :class="{ current: isActive('/categories') }"
                    >
                        <span class="ic">
                            <i class="bi bi-square"></i>
                        </span>

                        Categories
                    </Link>

                    <Link
                        href="/subscriptions"
                        class="nav-item"
                        :class="{ current: isActive('/subscriptions') }"
                    >
                        <span class="ic">
                            <i class="bi bi-arrow-repeat"></i>
                        </span>

                        Subscriptions
                    </Link>

                    <Link
                        href="/quantity-change-requests"
                        class="nav-item"
                        :class="{
                            current:
                                isActive('/quantity-change-requests')
                        }"
                    >
                        <span class="ic">
                            <i class="bi bi-arrow-left-right"></i>
                        </span>

                        Qty Change Requests
                    </Link>

                    <Link
                        href="/deliveries"
                        class="nav-item"
                        :class="{ current: isActive('/deliveries') }"
                    >
                        <span class="ic">
                            <i class="bi bi-table"></i>
                        </span>

                        Deliveries
                    </Link>

                    <Link
                        href="/routes"
                        class="nav-item"
                        :class="{ current: isActive('/routes') }"
                    >
                        <span class="ic">
                            <i class="bi bi-flag"></i>
                        </span>

                        Routes
                    </Link>

                    <Link
                        href="/delivery-boys"
                        class="nav-item"
                        :class="{ current: isActive('/delivery-boys') }"
                    >
                        <span class="ic">
                            <i class="bi bi-bicycle"></i>
                        </span>

                        Delivery Boys
                    </Link>


                </div>

                <div class="nav-group">
                    <div class="nav-group-label">
                        MONEY
                    </div>

                    <Link
                        href="/payments"
                        class="nav-item"
                        :class="{ current: isActive('/payments') }"
                    >
                        <span class="ic">
                            <i class="bi bi-check2"></i>
                        </span>

                        Payments
                    </Link>

                    <Link
                        href="/invoices"
                        class="nav-item"
                        :class="{ current: isActive('/invoices') }"
                    >
                        <span class="ic">
                            <i class="bi bi-receipt"></i>
                        </span>

                        Invoices
                    </Link>

                    <Link
                        href="/ledger"
                        class="nav-item"
                        :class="{ current: isActive('/ledger') }"
                    >
                        <span class="ic">
                            <i class="bi bi-card-list"></i>
                        </span>

                        Ledger
                    </Link>
                </div>
            </aside>

            <!-- Main -->
            <main class="main">
                <header class="topbar">
                    <button
                        type="button"
                        class="menu-btn"
                        @click="sidebarOpen = true"
                        aria-label="Open navigation"
                        :aria-expanded="sidebarOpen"
                    >
                        <i class="bi bi-list"></i>
                    </button>

                    <div>
                        <div class="title">
                            <slot name="title">
                                Admin
                            </slot>
                        </div>

                        <div class="meta">
                            <slot name="subtitle"></slot>
                        </div>
                    </div>

                    <div class="topbar-right">
                        <div class="search-box">
                            <i class="bi bi-search"></i>
                            Search customer, mobile, invoice...
                        </div>

                        <Link href="/notifications" class="btn secondary sm" aria-label="Notifications"><i class="bi bi-bell"></i></Link>

                        <slot name="header-action"></slot>
                        <Link href="/logout" method="post" as="button" class="btn secondary sm">Sign out</Link>

                        <div class="avatar">
                            {{ userInitials }}
                        </div>
                    </div>
                </header>

                <section class="content">
                    <div class="content-inner">
                        <div v-if="page.props.platform?.notice" class="alert alert-info" style="white-space: pre-wrap" role="status">{{ page.props.platform.notice }}</div>
                        <div v-if="page.props.flash?.success" class="alert alert-success" role="status">{{ page.props.flash.success }}</div>
                        <div v-if="page.props.flash?.error" class="alert alert-danger" role="alert">{{ page.props.flash.error }}</div>
                        <div v-if="page.props.errors?.delete" class="alert alert-danger" role="alert">{{ page.props.errors.delete }}</div>
                        <slot />
                    </div>
                </section>
            </main>
        </div>
    </div>
</template>
