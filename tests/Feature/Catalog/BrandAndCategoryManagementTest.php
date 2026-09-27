<?php

namespace Tests\Feature\Catalog;

use App\Enums\ProductType;
use App\Enums\UserRole;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandAndCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $salesperson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create([
            'role' => UserRole::Manager,
            'is_active' => true,
        ]);

        $this->salesperson = User::factory()->create([
            'role' => UserRole::Salesperson,
            'is_active' => true,
        ]);
    }

    public function test_manager_can_view_brands_and_categories_pages(): void
    {
        Brand::create(['name' => 'سامسونگ', 'slug' => 'samsung']);
        Category::create(['name' => 'گوشی موبایل', 'slug' => 'smartphones']);

        $response = $this->actingAs($this->manager)->get(route('brands.index'));
        $response->assertOk();
        $response->assertSee('سامسونگ');

        $response = $this->actingAs($this->manager)->get(route('categories.index'));
        $response->assertOk();
        $response->assertSee('گوشی موبایل');
    }

    public function test_manager_can_create_brand_with_auto_generated_slug(): void
    {
        $response = $this->actingAs($this->manager)->post(route('brands.store'), [
            'name' => 'اپل',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('brands.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('brands', [
            'name' => 'اپل',
            'is_active' => true,
        ]);

        $brand = Brand::where('name', 'اپل')->first();
        $this->assertNotNull($brand->slug);
    }

    public function test_brand_slug_handles_duplicates(): void
    {
        Brand::create(['name' => 'Apple', 'slug' => 'apple']);

        $response = $this->actingAs($this->manager)->post(route('brands.store'), [
            'name' => 'Apple Store',
            'slug' => 'apple',
        ]);

        // When duplicate slug is passed or auto-generated, controller resolves with suffix
        $this->assertDatabaseHas('brands', [
            'name' => 'Apple Store',
            'slug' => 'apple-1',
        ]);
    }

    public function test_manager_can_update_brand(): void
    {
        $brand = Brand::create(['name' => 'شیائومی', 'slug' => 'xiaomi', 'is_active' => true]);

        $response = $this->actingAs($this->manager)->put(route('brands.update', $brand), [
            'name' => 'شیائومی گلوبال',
            'slug' => 'xiaomi-global',
            'is_active' => '0',
        ]);

        $response->assertRedirect(route('brands.index'));

        $brand->refresh();
        $this->assertSame('شیائومی گلوبال', $brand->name);
        $this->assertSame('xiaomi-global', $brand->slug);
        $this->assertFalse($brand->is_active);
    }

    public function test_manager_can_create_product_with_automatically_generated_sku(): void
    {
        $response = $this->actingAs($this->manager)->post(route('products.store'), [
            'name' => 'کالای تستی',
            'type' => 'stock',
            'selling_price_toman' => 150000,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('products.index'));

        $product = Product::where('name', 'کالای تستی')->firstOrFail();
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku' => sprintf('PRD-%06d', $product->id),
            'selling_price_rials' => 1500000,
        ]);
    }

    public function test_cannot_delete_brand_with_associated_products(): void
    {
        $brand = Brand::create(['name' => 'سامسونگ', 'slug' => 'samsung']);

        Product::create([
            'name' => 'Galaxy S24',
            'type' => ProductType::Serialized,
            'brand_id' => $brand->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager)->delete(route('brands.destroy', $brand));

        $response->assertSessionHasErrors('brand_delete');
        $this->assertDatabaseHas('brands', ['id' => $brand->id]);
    }

    public function test_can_delete_brand_without_products(): void
    {
        $brand = Brand::create(['name' => 'برند تستی', 'slug' => 'test-brand']);

        $response = $this->actingAs($this->manager)->delete(route('brands.destroy', $brand));

        $response->assertRedirect(route('brands.index'));
        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
    }

    public function test_manager_can_create_update_and_delete_category(): void
    {
        // 1. Create
        $response = $this->actingAs($this->manager)->post(route('categories.store'), [
            'name' => 'لوازم جانبی',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', ['name' => 'لوازم جانبی']);

        $category = Category::where('name', 'لوازم جانبی')->first();

        // 2. Update
        $response = $this->actingAs($this->manager)->put(route('categories.update', $category), [
            'name' => 'اکسسوری و لوازم جانبی',
            'slug' => 'accessories',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('categories.index'));
        $category->refresh();
        $this->assertSame('اکسسوری و لوازم جانبی', $category->name);

        // 3. Delete without products
        $response = $this->actingAs($this->manager)->delete(route('categories.destroy', $category));
        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_cannot_delete_category_with_associated_products(): void
    {
        $category = Category::create(['name' => 'گوشی', 'slug' => 'phones']);

        Product::create([
            'name' => 'Pixel 8',
            'type' => ProductType::Serialized,
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager)->delete(route('categories.destroy', $category));

        $response->assertSessionHasErrors('category_delete');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_salesperson_cannot_create_or_update_brands_and_categories(): void
    {
        $brand = Brand::create(['name' => 'Nokia', 'slug' => 'nokia']);
        $category = Category::create(['name' => 'Feature Phones', 'slug' => 'feature-phones']);

        $this->actingAs($this->salesperson)
            ->post(route('brands.store'), ['name' => 'HMD'])
            ->assertForbidden();

        $this->actingAs($this->salesperson)
            ->put(route('brands.update', $brand), ['name' => 'Nokia Corp'])
            ->assertForbidden();

        $this->actingAs($this->salesperson)
            ->post(route('categories.store'), ['name' => 'Tablets'])
            ->assertForbidden();

        $this->actingAs($this->salesperson)
            ->put(route('categories.update', $category), ['name' => 'Basic Phones'])
            ->assertForbidden();
    }
}
