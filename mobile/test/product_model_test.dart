import 'package:flutter_test/flutter_test.dart';
import 'package:noir_app/models/product.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('Product Model Tests', () {
    test('Product.fromJson parses valid json correctly', () {
      final json = {
        'id': 101,
        'name': 'Cashmere Trench Coat',
        'price': '85000',
        'compare_price': '110000',
        'category': 'Outerwear',
        'brand': 'Noir Luxe',
        'description': 'Handcrafted pure cashmere trench coat.',
        'stock': 12,
        'slug': 'cashmere-trench-coat',
        'in_stock': 1,
      };

      final product = Product.fromJson(json);

      expect(product.id, equals(101));
      expect(product.name, equals('Cashmere Trench Coat'));
      expect(product.price, equals(85000.0));
      expect(product.comparePrice, equals(110000.0));
      expect(product.category, equals('Outerwear'));
      expect(product.brandName, equals('Noir Luxe'));
      expect(product.stock, equals(12));
      expect(product.inStock, isTrue);
      expect(product.hasDiscount, isTrue);
      expect(product.formattedPrice, equals('₦85,000'));
      expect(product.formattedComparePrice, equals('₦110,000'));
    });

    test('Product.fromJson handles missing optional fields with sensible defaults', () {
      final json = {
        'id': '205',
        'name': 'Minimal Linen Shirt',
        'price': 42000,
      };

      final product = Product.fromJson(json);

      expect(product.id, equals(205));
      expect(product.name, equals('Minimal Linen Shirt'));
      expect(product.price, equals(42000.0));
      expect(product.comparePrice, isNull);
      expect(product.stock, equals(0));
      expect(product.hasDiscount, isFalse);
      expect(product.formattedComparePrice, isEmpty);
    });

    test('ProductsResult.fromJson parses list and pagination metadata', () {
      final json = {
        'products': [
          {'id': 1, 'name': 'Item One', 'price': 10000},
          {'id': 2, 'name': 'Item Two', 'price': 25000},
        ],
        'pagination': {
          'page': 2,
          'pages': 5,
          'total': 50,
        },
      };

      final result = ProductsResult.fromJson(json);

      expect(result.products.length, equals(2));
      expect(result.products[0].name, equals('Item One'));
      expect(result.products[1].price, equals(25000.0));
      expect(result.page, equals(2));
      expect(result.totalPages, equals(5));
      expect(result.total, equals(50));
    });

    test('Product.toJson produces structured map', () {
      final product = Product(
        id: 7,
        name: 'Noir Silk Scarf',
        price: 18500,
        stock: 5,
        brandName: 'Noir Studio',
      );

      final map = product.toJson();

      expect(map['id'], equals(7));
      expect(map['name'], equals('Noir Silk Scarf'));
      expect(map['price'], equals(18500.0));
      expect(map['stock'], equals(5));
      expect(map['brand_name'], equals('Noir Studio'));
    });
  });
}
