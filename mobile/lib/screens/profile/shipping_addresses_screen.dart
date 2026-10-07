import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../theme.dart';
import '../../providers/auth_provider.dart';
import '../../l10n/app_strings.dart';
import 'add_address_screen.dart';

class ShippingAddressesScreen extends StatefulWidget {
  const ShippingAddressesScreen({super.key});

  @override
  State<ShippingAddressesScreen> createState() => _ShippingAddressesScreenState();
}

class _ShippingAddressesScreenState extends State<ShippingAddressesScreen> {
  List<Map<String, dynamic>> _addresses = [];
  bool _loading = true;

  String _getUserStorageKey() {
    try {
      final user = context.read<AuthProvider>().user;
      if (user != null && user.id > 0) {
        return 'noir_user_${user.id}_saved_shipping_addresses';
      }
    } catch (_) {}
    return 'noir_guest_saved_shipping_addresses';
  }

  @override
  void initState() {
    super.initState();
    _loadAddresses();
  }

  Future<void> _loadAddresses() async {
    final prefs = await SharedPreferences.getInstance();
    final key = _getUserStorageKey();
    final data = prefs.getString(key);
    if (data != null && data.isNotEmpty) {
      try {
        final List<dynamic> decoded = jsonDecode(data);
        if (mounted) {
          setState(() {
            _addresses = decoded.map((e) => Map<String, dynamic>.from(e)).toList();
            _loading = false;
          });
          return;
        }
      } catch (_) {}
    }
    if (mounted) {
      setState(() {
        _addresses = [];
        _loading = false;
      });
    }
  }

  Future<void> _saveAddresses() async {
    final prefs = await SharedPreferences.getInstance();
    final key = _getUserStorageKey();
    await prefs.setString(key, jsonEncode(_addresses));
  }

  void _setDefaultAddress(int index) {
    setState(() {
      for (int i = 0; i < _addresses.length; i++) {
        _addresses[i]['is_default'] = (i == index);
      }
    });
    _saveAddresses();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Primary delivery destination updated to "${_addresses[index]['address_line1']}".'),
        backgroundColor: NoirTheme.primary,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _removeAddress(int index) {
    final removed = _addresses[index];
    setState(() {
      _addresses.removeAt(index);
      if (removed['is_default'] == true && _addresses.isNotEmpty) {
        _addresses.first['is_default'] = true;
      }
    });
    _saveAddresses();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Removed address for ${removed['name']}'),
        backgroundColor: const Color(0xFF1E211A),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _openAddAddress() async {
    final res = await Navigator.push(
      context,
      MaterialPageRoute(builder: (_) => const AddAddressScreen()),
    );
    if (res != null && res is Map<String, dynamic>) {
      setState(() {
        if (res['is_default'] == true || _addresses.isEmpty) {
          for (var a in _addresses) {
            a['is_default'] = false;
          }
          res['is_default'] = true;
        }
        _addresses.add(res);
      });
      _saveAddresses();
    }
  }

  @override
  Widget build(BuildContext context) {
    final strings = AppStrings.of(context);

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded, size: 20),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text(
          strings.shippingAddresses,
          style: GoogleFonts.urbanist(fontSize: 18, fontWeight: FontWeight.w800),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.add_location_alt_outlined),
            onPressed: _openAddAddress,
          ),
        ],
      ),
      body: _loading
          ? Center(child: CircularProgressIndicator(color: NoirTheme.primary))
          : SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    strings.shippingDelivery,
                    style: GoogleFonts.urbanist(fontSize: 18, fontWeight: FontWeight.w900),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    strings.streetAddress,
                    style: GoogleFonts.urbanist(color: Colors.grey, fontSize: 13),
                  ),
                  const SizedBox(height: 20),

                  if (_addresses.isEmpty)
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(36),
                      alignment: Alignment.center,
                      decoration: BoxDecoration(
                        color: Theme.of(context).colorScheme.surface,
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(
                            color: Theme.of(context).colorScheme.outline.withValues(alpha: 0.3)),
                      ),
                      child: Column(
                        children: [
                          Icon(Icons.location_off_outlined, size: 48, color: Colors.grey.shade600),
                          const SizedBox(height: 14),
                          Text(
                            strings.noAddresses,
                            style: GoogleFonts.urbanist(fontWeight: FontWeight.w900, fontSize: 16),
                          ),
                          const SizedBox(height: 20),
                          ElevatedButton.icon(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: NoirTheme.primary,
                              foregroundColor: Colors.white,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                            icon: const Icon(Icons.add_rounded, size: 18),
                            label: Text(strings.addNewAddress),
                            onPressed: _openAddAddress,
                          ),
                        ],
                      ),
                    )
                  else
                    ...List.generate(_addresses.length, (i) {
                      final addr = _addresses[i];
                      final isDefault = addr['is_default'] == true || addr['is_default'] == '1';
                      final name = addr['name'] ?? 'Recipient';
                      final phone = addr['phone'] ?? '';
                      final street = addr['address_line1'] ?? '';
                      final city = addr['city'] ?? '';
                      final state = addr['state'] ?? '';
                      final country = addr['country'] ?? 'Nigeria';

                      return Container(
                        margin: const EdgeInsets.only(bottom: 14),
                        padding: const EdgeInsets.all(18),
                        decoration: BoxDecoration(
                          color: Theme.of(context).colorScheme.surface,
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(
                            color: isDefault
                                ? NoirTheme.primary
                                : Theme.of(context).colorScheme.outline.withValues(alpha: 0.3),
                            width: isDefault ? 1.5 : 1,
                          ),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Row(
                                  children: [
                                    Icon(
                                      Icons.location_on_rounded,
                                      color: isDefault ? NoirTheme.primary : Colors.grey,
                                      size: 22,
                                    ),
                                    const SizedBox(width: 10),
                                    Text(
                                      name,
                                      style: GoogleFonts.urbanist(
                                          fontWeight: FontWeight.w900, fontSize: 15.5),
                                    ),
                                  ],
                                ),
                                if (isDefault)
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                    decoration: BoxDecoration(
                                      color: NoirTheme.primary.withValues(alpha: 0.15),
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Text(
                                      strings.setDefault.toUpperCase(),
                                      style: TextStyle(
                                          color: NoirTheme.primary,
                                          fontSize: 10,
                                          fontWeight: FontWeight.w900),
                                    ),
                                  ),
                              ],
                            ),
                            const SizedBox(height: 10),
                            Text(
                              '$street, $city, $state, $country',
                              style: GoogleFonts.urbanist(
                                  fontSize: 13.5, height: 1.4, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.8)),
                            ),
                            if (phone.isNotEmpty) ...[
                              const SizedBox(height: 4),
                              Text(
                                '${strings.phoneNumber}: $phone',
                                style: GoogleFonts.urbanist(fontSize: 12.5, color: Colors.grey),
                              ),
                            ],
                            const SizedBox(height: 14),
                            Row(
                              children: [
                                if (!isDefault)
                                  TextButton(
                                    onPressed: () => _setDefaultAddress(i),
                                    child: Text(strings.setDefault,
                                        style: GoogleFonts.urbanist(
                                            fontWeight: FontWeight.bold, color: NoirTheme.primary)),
                                  ),
                                const Spacer(),
                                IconButton(
                                  icon: const Icon(Icons.delete_outline_rounded,
                                      color: Colors.redAccent, size: 20),
                                  onPressed: () => _removeAddress(i),
                                ),
                              ],
                            ),
                          ],
                        ),
                      );
                    }),

                  const SizedBox(height: 20),

                  if (_addresses.isNotEmpty)
                    SizedBox(
                      width: double.infinity,
                      child: OutlinedButton.icon(
                        icon: const Icon(Icons.add_rounded),
                        label: Text(strings.addNewAddress),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: NoirTheme.primary,
                          side: BorderSide(color: NoirTheme.primary),
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        ),
                        onPressed: _openAddAddress,
                      ),
                    ),
                ],
              ),
            ),
    );
  }
}
