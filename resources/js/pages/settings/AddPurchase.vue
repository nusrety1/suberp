<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Müşteriler',
        href: '/customers',
    },
    {
        title: 'Müşteri Ekle',
        href: '/add-customer',
    },
];

const form = useForm({
    name: '',
    surname: '',
    email: '',
    phone: '',
    address: '',
    description: ''
});

const submitForm = () => {
    form.post(route('create-customer'), {
        onSuccess: () => {
            console.log('Müşteri başarıyla eklendi');
        },
        onError: (errors) => {
            console.log('Form hataları:', errors);
        }
    });
};
</script>

<template>
    <Head title="Müşteri Ekle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Sayfa Başlığı -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Yeni Müşteri Ekle
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Müşteri bilgilerini doldurun ve sisteme kaydedin.
                </p>
            </div>

            <!-- Ana Form Kartı -->
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg overflow-hidden">
                <form @submit.prevent="submitForm" class="p-6 sm:p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Ad -->
                        <div class="space-y-2">
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Ad <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200"
                                placeholder="Müşterinin adı"
                                :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- Soyad -->
                        <div class="space-y-2">
                            <label for="surname" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Soyad <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="surname"
                                v-model="form.surname"
                                type="text"
                                required
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200"
                                placeholder="Müşterinin soyadı"
                                :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.surname }"
                            />
                            <p v-if="form.errors.surname" class="text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.surname }}
                            </p>
                        </div>

                        <!-- E-posta -->
                        <div class="space-y-2">
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                E-posta <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200"
                                placeholder="ornek@email.com"
                                :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.email }"
                            />
                            <p v-if="form.errors.email" class="text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <!-- Telefon -->
                        <div class="space-y-2">
                            <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Telefon Numarası <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="phone"
                                v-model="form.phone"
                                type="tel"
                                required
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200"
                                placeholder="+90 (555) 123 45 67"
                                :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.phone }"
                            />
                            <p v-if="form.errors.phone" class="text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.phone }}
                            </p>
                        </div>
                    </div>

                    <!-- Adres - Tam genişlik -->
                    <div class="mt-6 space-y-2">
                        <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Adres <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            id="address"
                            v-model="form.address"
                            rows="3"
                            required
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200 resize-none"
                            placeholder="Müşterinin tam adresi..."
                            :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.address }"
                        ></textarea>
                        <p v-if="form.errors.address" class="text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.address }}
                        </p>
                    </div>

                    <!-- Notlar -->
                    <div class="mt-6 space-y-2">
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Notlar
                        </label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200 resize-none"
                            placeholder="Müşteri hakkında ek bilgiler..."
                            :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.description }"
                        ></textarea>
                        <p v-if="form.errors.description" class="text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.description }}
                        </p>
                    </div>

                    <!-- Form Butonları -->
                    <div class="mt-8 flex flex-col sm:flex-row gap-4 sm:justify-end">
                        <button
                            type="button"
                            @click="$inertia.visit('/customers')"
                            class="w-full sm:w-auto px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200"
                        >
                            İptal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full sm:w-auto px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-lg focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200 flex items-center justify-center gap-2"
                        >
                            <svg v-if="form.processing" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ form.processing ? 'Kaydediliyor...' : 'Müşteri Ekle' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Bilgi Notu -->
            <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                            Bilgi
                        </h3>
                        <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                            Zorunlu alanlar (*) işaretli olanlardır. Müşteri bilgileri kaydedildikten sonra düzenlenebilir.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
