<?php

namespace Tests\Feature;

use Tests\TestCase;

class ListProductsTest extends TestCase
{
    /**
     * Test para listar productos, respondera error ya que se enviara sin token
     */
    public function test_list_products_requires_authentication()
    {
        $response = $this->getJson('/api/product/listProduct');

        $response->assertStatus(401); // Sin token debe devolver 401
    }

    /**
     * Test para listar productos, responde correctamente
     */
    public function test_can_list_products_after_login()
    {
        $user = \App\Models\User::factory()->create();
        $token = $user->createToken('TestToken')->accessToken;
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/product/listProduct');
        $response->assertStatus(200);
        $responseData = $response->json();
        // filtra la respuesta y muestra el contenido completo de 'original' y luego 'data'
        $originalData = $responseData['original'];
        $data = $originalData['data'];
        dd($data);
        $this->assertNotEmpty($data);
    }
}
