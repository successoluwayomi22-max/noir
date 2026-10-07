import 'package:flutter_test/flutter_test.dart';
import 'package:noir_app/models/app_user.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('AppUser Model Tests', () {
    test('AppUser.fromJson parses customer account correctly', () {
      final json = {
        'id': 42,
        'name': 'Elena Rostova',
        'email': 'elena@example.com',
        'username': 'elena_r',
        'phone': '+2348012345678',
        'avatar': '/uploads/avatars/elena.jpg',
        'public_id': 'USR-4299ABC',
        'account_type': 'customer',
        'roles': ['customer', 'vip'],
        'loyalty_points': 350,
        'vip_tier': 'Gold',
      };

      final user = AppUser.fromJson(json);

      expect(user.id, equals(42));
      expect(user.name, equals('Elena Rostova'));
      expect(user.email, equals('elena@example.com'));
      expect(user.username, equals('elena_r'));
      expect(user.phone, equals('+2348012345678'));
      expect(user.avatar, equals('/uploads/avatars/elena.jpg'));
      expect(user.accountType, equals('customer'));
      expect(user.roles, contains('vip'));
      expect(user.loyaltyPoints, equals(350));
      expect(user.vipTier, equals('Gold'));
      expect(user.initial, equals('E'));
      expect(user.isAdmin, isFalse);
      expect(user.hasRole('vip'), isTrue);
      expect(user.hasRole('super-admin'), isFalse);
    });

    test('AppUser detects admin privileges accurately', () {
      final adminUser = AppUser.fromJson({
        'id': 1,
        'name': 'Administrator',
        'email': 'admin@noir.test',
        'account_type': 'admin',
        'roles': ['admin', 'super-admin'],
      });

      expect(adminUser.isAdmin, isTrue);
      expect(adminUser.hasRole('admin'), isTrue);

      final staffUser = AppUser.fromJson({
        'id': 2,
        'name': 'Store Staff',
        'email': 'staff@noir.test',
        'account_type': 'staff',
        'roles': ['store-manager'],
      });

      expect(staffUser.isAdmin, isTrue);
      expect(staffUser.hasRole('store-manager'), isTrue);
    });

    test('AppUser copyWith updates fields correctly', () {
      final user = AppUser(
        id: 10,
        name: 'John Doe',
        email: 'john@example.com',
        loyaltyPoints: 100,
      );

      final updated = user.copyWith(
        name: 'Jonathan Doe',
        loyaltyPoints: 250,
        vipTier: 'Silver',
      );

      expect(updated.id, equals(10));
      expect(updated.name, equals('Jonathan Doe'));
      expect(updated.email, equals('john@example.com'));
      expect(updated.loyaltyPoints, equals(250));
      expect(updated.vipTier, equals('Silver'));
    });

    test('AppUser.toJson produces complete key-value mapping', () {
      final user = AppUser(
        id: 5,
        name: 'Amina Bello',
        email: 'amina@noir.test',
        accountType: 'customer',
        roles: ['customer'],
        loyaltyPoints: 50,
      );

      final json = user.toJson();

      expect(json['id'], equals(5));
      expect(json['name'], equals('Amina Bello'));
      expect(json['email'], equals('amina@noir.test'));
      expect(json['account_type'], equals('customer'));
      expect(json['loyalty_points'], equals(50));
    });
  });
}
