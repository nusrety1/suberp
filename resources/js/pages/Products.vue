<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Package, Upload } from 'lucide-vue-next';

defineProps<{
    categories?: Array<{ id: number; name: string }>;
    product?: {
        id?: number;
        name: string;
        description: string;
        price: number;
        category_id: number;
        stock_quantity: number;
        sku: string;
        is_active: boolean;
        image_url?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Products',
        href: '/products',
    },
    {
        title: 'Add Product',
        href: '/products/create',
    },
];

const form = useForm({
    name: '',
    description: '',
    price: 0,
    category_id: '',
    stock_quantity: 0,
    sku: '',
    is_active: true,
    image: null as File | null,
});

const submit = () => {
    form.post(route('products.store'), {
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Add Product" />

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
                                    placeholder="Enter product name"
                                    class="h-11"
                                />
                                <InputError :message="form.errors.name" />
                            </div>

                            <div class="space-y-2">
                                <Label for="sku" class="text-sm font-semibold text-gray-700">
                                    Stok Kodu <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="sku"
                                    type="text"
                                    required
                                    :tabindex="2"
                                    v-model="form.sku"
                                    placeholder="e.g., PRD-001"
                                    class="h-11"
                                />
                                <InputError :message="form.errors.sku" />
                            </div>
                        </div>

                        <!-- Product Description -->
                        <div class="space-y-2">
                            <Label for="description" class="text-sm font-semibold text-gray-700">
                                Ürün Açıklaması
                            </Label>
                            <Textarea
                                id="description"
                                :tabindex="3"
                                v-model="form.description"
                                placeholder="Describe your product in detail..."
                                rows="4"
                                class="resize-none"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <!-- Price and Stock -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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

                            <div class="space-y-2">
                                <Label for="stock_quantity" class="text-sm font-semibold text-gray-700">
                                    Stok Adedi <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="stock_quantity"
                                    type="number"
                                    min="0"
                                    required
                                    :tabindex="5"
                                    v-model="form.stock_quantity"
                                    placeholder="0"
                                    class="h-11"
                                />
                                <InputError :message="form.errors.stock_quantity" />
                            </div>

                            <div class="space-y-2">
                                <Label for="category_id" class="text-sm font-semibold text-gray-700">
                                    Kategori <span class="text-red-500">*</span>
                                </Label>
                                <Select v-model="form.category_id" required>
                                    <SelectTrigger class="h-11" :tabindex="6">
                                        <SelectValue placeholder="Select a category" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="1">Electronics</SelectItem>
                                        <SelectItem value="2">Clothing</SelectItem>
                                        <SelectItem value="3">Books</SelectItem>
                                        <SelectItem value="4">Home & Garden</SelectItem>
                                        <SelectItem value="5">Sports</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.category_id" />
                            </div>
                        </div>

                        <!-- Product Status -->
                        <div class="flex items-center space-x-3 p-4 bg-gray-50 rounded-lg">
                            <Checkbox
                                id="is_active"
                                v-model="form.is_active"
                                :tabindex="8"
                                class="data-[state=checked]:bg-green-600 data-[state=checked]:border-green-600"
                            />
                            <Label for="is_active" class="text-sm font-medium text-gray-700 cursor-pointer">
                                Ürün Satış İçin Aktif Durumu
                            </Label>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t">
                            <Button
                                type="submit"
                                class="flex-1 h-12 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 font-semibold"
                                :tabindex="9"
                                :disabled="form.processing"
                            >
                                <LoaderCircle v-if="form.processing" class="h-5 w-5 animate-spin mr-2" />
                                <Package v-else class="h-5 w-5 mr-2" />
                                {{ form.processing ? 'Adding Product...' : 'Add Product' }}
                            </Button>

                            <Button
                                type="button"
                                variant="outline"
                                class="flex-1 h-12 font-semibold"
                                :tabindex="10"
                                @click="form.reset()"
                            >
                                Clear Form
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
