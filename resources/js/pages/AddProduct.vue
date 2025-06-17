<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Package } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Ürünler',
        href: '/products',
    },
    {
        title: 'Ürün Ekle',
        href: '/products/create',
    },
];

const form = useForm({
    name: '',
    price: 0,
    old_price: 0,
    description: '',
});

const submit = () => {
    form.post(route('create-product'), {
        onSuccess: () => {
            console.log('Ürün Başarıyla Eklendi');
            form.reset();
        },
        onError: (errors) => {
            console.log('Form hataları:', errors);
        }
    });
};
</script>

<template>
    <Head title="Ürün Ekle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto py-8">
            <Card class="shadow-lg">
                <CardHeader class="bg-gradient-to-r from-blue-50 to-indigo-50 border-b">
                    <div class="flex items-center gap-3 mt-6">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <Package class="h-6 w-6 text-blue-600" />
                        </div>
                        <div>
                            <CardTitle class="text-2xl font-bold text-gray-900">Yeni Ürün Ekle</CardTitle>
                            <CardDescription class="text-gray-600 mt-1">
                                Envanterinize yeni bir ürün eklemek için aşağıdaki bilgileri doldurun.
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="p-8">
                    <form @submit.prevent="submit" class="space-y-8">
                        <!-- Product Basic Information -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <Label for="name" class="text-sm font-semibold text-gray-700">
                                    Ürün Adı <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="name"
                                    type="text"
                                    required
                                    autofocus
                                    :tabindex="1"
                                    v-model="form.name"
                                    placeholder="Ürün adını girin"
                                    class="h-11"
                                />
                                <InputError :message="form.errors.name" />
                            </div>

                            <div class="space-y-2">
                                <Label for="price" class="text-sm font-semibold text-gray-700">
                                    Fiyat (₺) <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    required
                                    :tabindex="4"
                                    v-model="form.price"
                                    placeholder="0.00"
                                    class="h-11"
                                />
                                <InputError :message="form.errors.price" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="description" class="text-sm font-semibold text-gray-700">
                                Ürün Açıklaması
                            </Label>
                            <input
                                id="description"
                                type="text"
                                :tabindex="3"
                                v-model="form.description"
                                placeholder="Ürününüzü detaylı bir şekilde açıklayın..."
                                rows="4"
                                class="resize-none"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <!-- Form Actions -->
                        <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t">
                            <Button
                                type="submit"
                                class="flex-1 h-12 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 font-semibold"
                                :tabindex="6"
                                :disabled="form.processing"
                            >
                                <LoaderCircle v-if="form.processing" class="h-5 w-5 animate-spin mr-2" />
                                <Package v-else class="h-5 w-5 mr-2" />
                                {{ form.processing ? 'Ürün Ekleniyor...' : 'Ürün Ekle' }}
                            </Button>

                            <Button
                                type="button"
                                variant="outline"
                                class="flex-1 h-12 font-semibold"
                                :tabindex="7"
                                @click="form.reset()"
                            >
                                Formu Temizle
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
