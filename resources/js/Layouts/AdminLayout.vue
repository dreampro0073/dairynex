<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const page = usePage()

const sidebarOpen = ref(false)

const currentUrl = computed(() => page.url)

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
                        SK
                    </div>

                    <div>
                        <div class="name">
                            Shree Krishna Dairy
                        </div>

                        <div class="sub">
                            Premium · Haridwar
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

                    <Link
                        href="/pin-verification"
                        class="nav-item"
                        :class="{
                            current: isActive('/pin-verification')
                        }"
                    >
                        <span class="ic">
                            <i class="bi bi-key"></i>
                        </span>

                        PIN Verification
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

                        <button
                            type="button"
                            class="btn secondary sm"
                        >
                            <i class="bi bi-bell"></i>
                        </button>

                        <slot name="header-action"></slot>

                        <div class="avatar">
                            RK
                        </div>
                    </div>
                </header>

                <section class="content">
                    <div class="content-inner">
                        <slot />
                    </div>
                </section>
            </main>
        </div>
    </div>
</template>