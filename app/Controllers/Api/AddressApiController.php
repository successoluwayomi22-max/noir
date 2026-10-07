<?php
namespace Noir\App\Controllers\Api;

use Noir\Core\Container;
use Noir\Core\Request;

final class AddressApiController
{
    public function index(Request $req): never
    {
        if (empty($req->user['id'])) {
            json(['error' => 'Unauthorized'], 401);
        }
        $r = new \Noir\App\Repositories\CustomerRepository(Container::get('db'));
        $addresses = $r->addresses((int)$req->user['id']);
        
        json(['success' => true, 'addresses' => $addresses]);
    }

    public function store(Request $req): never
    {
        if (empty($req->user['id'])) {
            json(['error' => 'Unauthorized'], 401);
        }
        $r = new \Noir\App\Repositories\CustomerRepository(Container::get('db'));
        
        $data = [
            'label' => trim((string)$req->input('label', 'Home')),
            'recipient_name' => trim((string)$req->input('name')),
            'address_line' => trim((string)$req->input('address_line1')),
            'city' => trim((string)$req->input('city')),
            'state' => trim((string)$req->input('state')),
            'country' => trim((string)$req->input('country', 'Nigeria')),
            'phone' => trim((string)$req->input('phone')),
            'is_default' => $req->input('is_default')
        ];
        
        $r->addAddress((int)$req->user['id'], $data);

        json(['success' => true, 'message' => 'Address added']);
    }
    
    public function destroy(Request $req, $id): never
    {
        if (empty($req->user['id'])) {
            json(['error' => 'Unauthorized'], 401);
        }
        $r = new \Noir\App\Repositories\CustomerRepository(Container::get('db'));
        $r->deleteAddress((int)$req->user['id'], (int)$id);

        
        json(['success' => true, 'message' => 'Address deleted']);
    }
}
