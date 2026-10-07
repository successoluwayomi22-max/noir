<?php
declare(strict_types=1);

namespace Noir\App\Services;

/** Replace this class with repositories when the customer module is connected to MySQL. */
final class CustomerMockData
{
    public static function customer(): array { return ['id' => 'CUS-2026-0001', 'username' => 'adeola.noir', 'name' => 'Adeola Williams', 'email' => 'adeola@example.com', 'initials' => 'AW']; }
    public static function stats(): array { return ['orders' => 12, 'pending' => 2, 'delivered' => 9, 'wishlist' => 8, 'wallet' => 185000, 'points' => 2450]; }
    public static function orders(): array { return [['number'=>'NC-10482','date'=>'05 Aug 2026','status'=>'Delivered','payment'=>'Paid','total'=>84500,'currency'=>'NGN'],['number'=>'NC-10477','date'=>'02 Aug 2026','status'=>'Processing','payment'=>'Paid','total'=>42000,'currency'=>'NGN'],['number'=>'NC-10463','date'=>'28 Jul 2026','status'=>'Pending','payment'=>'Pending','total'=>125000,'currency'=>'NGN'],['number'=>'NC-10390','date'=>'15 Jul 2026','status'=>'Delivered','payment'=>'Paid','total'=>31800,'currency'=>'NGN']]; }
    public static function order(string $number): array { return ['number'=>$number,'date'=>'05 Aug 2026','status'=>'Delivered','payment'=>'Paid','total'=>84500,'currency'=>'NGN','shipping'=>['name'=>'Adeola Williams','line'=>'14 Adebayo Street','city'=>'Lekki, Lagos','phone'=>'+234 801 234 5678'],'billing'=>['name'=>'Adeola Williams','line'=>'14 Adebayo Street','city'=>'Lekki, Lagos'],'items'=>[['name'=>'Stoneware Table Set','quantity'=>1,'price'=>52000,'image'=>'#d6d1c4'],['name'=>'Olive Linen Throw','quantity'=>1,'price'=>32500,'image'=>'#788369']]]; }
    public static function products(): array { return [['id'=>1,'name'=>'Stoneware Table Set','price'=>52000,'category'=>'Homeware','image'=>'#d6d1c4'],['id'=>2,'name'=>'Olive Linen Throw','price'=>32500,'category'=>'Textiles','image'=>'#788369'],['id'=>3,'name'=>'Form Candle Holder','price'=>18500,'category'=>'Objects','image'=>'#3b4039'],['id'=>4,'name'=>'Daily Ritual Mug','price'=>12000,'category'=>'Kitchen','image'=>'#b9ad9b']]; }
    public static function wishlist(): array { return array_slice(self::products(), 0, 3); }
    public static function viewed(): array { return array_slice(self::products(), 1, 3); }
    public static function coupons(): array { return [['code'=>'WELCOME15','text'=>'15% off your next order','expires'=>'Expires 31 Aug 2026']]; }
    public static function notifications(): array { return [['title'=>'Your order NC-10482 was delivered','time'=>'2 hours ago','type'=>'success'],['title'=>'You earned 450 reward points','time'=>'Yesterday','type'=>'reward'],['title'=>'New pieces have arrived in the collection','time'=>'3 days ago','type'=>'info']]; }
    public static function addresses(): array { return [['id'=>1,'label'=>'Home','name'=>'Adeola Williams','line'=>'14 Adebayo Street','city'=>'Lekki, Lagos','phone'=>'+234 801 234 5678','default'=>true],['id'=>2,'label'=>'Office','name'=>'Adeola Williams','line'=>'22 Marina Road','city'=>'Lagos Island, Lagos','phone'=>'+234 801 234 5678','default'=>false]]; }
    public static function payments(): array { return [['type'=>'Visa','last4'=>'4242','expires'=>'09/28','default'=>true],['type'=>'Wallet','last4'=>'','expires'=>'₦185,000 balance','default'=>false]]; }
    public static function transactions(): array { return [['date'=>'05 Aug 2026','description'=>'Wallet top up','type'=>'Credit','amount'=>100000],['date'=>'05 Aug 2026','description'=>'Order NC-10482','type'=>'Debit','amount'=>-84500],['date'=>'22 Jul 2026','description'=>'Reward points conversion','type'=>'Credit','amount'=>25000]]; }
    public static function reviews(): array { return [['product'=>'Stoneware Table Set','rating'=>5,'text'=>'Beautiful quality and exactly as pictured.','date'=>'07 Aug 2026'],['product'=>'Olive Linen Throw','rating'=>4,'text'=>'Soft, rich colour and fast delivery.','date'=>'20 Jul 2026']]; }
    public static function tickets(): array { return [['id'=>'TKT-1008','subject'=>'Question about delivery','status'=>'Open','date'=>'06 Aug 2026'],['id'=>'TKT-1002','subject'=>'Exchange request','status'=>'Resolved','date'=>'28 Jul 2026']]; }
}
