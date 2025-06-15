<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import type { BreadcrumbItem } from '@/types';

interface Customer {
    id: number;
    name: string;
    email: string;
    phone: string;
    address: string;
    description?: string;
    created_at: string;
    updated_at: string;
}

interface PaginatedCustomers {
    data: Customer[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    next_page_url?: string;
    prev_page_url?: string;
}

interface Props {
    customers: PaginatedCustomers;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Müşteriler',
        href: '/customers',
    },
];

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('tr-TR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>

<template>
    <Head title="Müşteriler" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Müşteriler</h1>
                    <p class="text-gray-600 mt-1">Toplam {{ props.customers.total }} müşteri</p>
                </div>
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

            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Müşteri
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                İletişim
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Adres
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Not
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Kayıt Tarihi
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="customer in props.customers.data" :key="customer.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                <span class="text-sm font-medium text-blue-600">
                                                    {{ customer.name.charAt(0).toUpperCase() }}
                                                </span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ customer.name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            ID: #{{ customer.id }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ customer.email }}</div>
                                <div class="text-sm text-gray-500" v-if="customer.phone">
                                    {{ customer.phone }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center gap-2">
                                    {{ customer.address}}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center gap-2">
                                    {{ customer.description}}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ formatDate(customer.created_at) }}
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6" v-if="props.customers.last_page > 1">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <button
                            v-if="props.customers.prev_page_url"
                            @click="$inertia.visit(props.customers.prev_page_url)"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Önceki
                        </button>
                        <button
                            v-if="props.customers.next_page_url"
                            @click="$inertia.visit(props.customers.next_page_url)"
                            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Sonraki
                        </button>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Toplam <span class="font-medium">{{ props.customers.total }}</span> sonuçtan
                                <span class="font-medium">{{ ((props.customers.current_page - 1) * props.customers.per_page) + 1 }}</span>
                                -
                                <span class="font-medium">{{ Math.min(props.customers.current_page * props.customers.per_page, props.customers.total) }}</span>
                                arası gösteriliyor
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <button
                                    v-if="props.customers.prev_page_url"
                                    @click="$inertia.visit(props.customers.prev_page_url)"
                                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                >
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                    {{ props.customers.current_page }} / {{ props.customers.last_page }}
                                </span>

                                <button
                                    v-if="props.customers.next_page_url"
                                    @click="$inertia.visit(props.customers.next_page_url)"
                                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                >
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="props.customers.data.length === 0" class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.196-2.196M17 20H7m10 0v-2c0-5.523-4.477-10-10-10s-10 4.477-10 10v2m10 0H7m10 0H7M7 20H2v-2a3 3 0 015.196-2.196M7 20v-2m5-16a3 3 0 110 6 3 3 0 010-6zM2 18h16" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Henüz müşteri yok</h3>
                <p class="mt-1 text-sm text-gray-500">İlk müşterinizi ekleyerek başlayın.</p>
                <div class="mt-6">
                    <button
                        @click="$inertia.visit('/add-customer')"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Yeni Müşteri Ekle
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
