<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Müşteriler',
        href: '/customers',
    },
];
</script>

<template>
    <Head title="Müşteriler" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="m-5">
            <button
                @click="$inertia.visit('/add-customer')"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Yeni Müşteri Ekle
            </button>
        </div>
    </AppLayout>
</template>
