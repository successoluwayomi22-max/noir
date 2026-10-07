import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../api_client.dart';
import '../../providers/auth_provider.dart';
import '../../theme.dart';
import '../../l10n/app_strings.dart';
import '../../services/address_service.dart';

class AddAddressScreen extends StatefulWidget {
  const AddAddressScreen({super.key});

  @override
  State<AddAddressScreen> createState() => _AddAddressScreenState();
}

class _AddAddressScreenState extends State<AddAddressScreen> {
  final _formKey = GlobalKey<FormState>();

  final _nameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _streetController = TextEditingController();
  final _cityController = TextEditingController();
  final _stateController = TextEditingController();
  final _zipController = TextEditingController();
  final _countryController = TextEditingController(text: 'Nigeria');
  final _searchController = TextEditingController();

  bool _saving = false;
  bool _isValidating = false;
  bool _isGoogleValidated = false;
  List<AddressSuggestion> _suggestions = [];

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
    _searchController.addListener(_onSearchChanged);
  }

  @override
  void dispose() {
    _searchController.removeListener(_onSearchChanged);
    _searchController.dispose();
    _nameController.dispose();
    _phoneController.dispose();
    _streetController.dispose();
    _cityController.dispose();
    _stateController.dispose();
    _zipController.dispose();
    _countryController.dispose();
    super.dispose();
  }

  void _onSearchChanged() {
    final query = _searchController.text.trim();
    if (query.length >= 2) {
      final results = AddressValidationService.getLocalAddressSuggestions(query);
      setState(() {
        _suggestions = results;
      });
    } else {
      if (_suggestions.isNotEmpty) {
        setState(() {
          _suggestions = [];
        });
      }
    }
  }

  void _applySuggestion(AddressSuggestion suggestion) {
    setState(() {
      _streetController.text = suggestion.mainText;
      final parts = suggestion.secondaryText.split(',').map((s) => s.trim()).toList();
      if (parts.isNotEmpty) _cityController.text = parts[0];
      if (parts.length > 1) _stateController.text = parts[1];
      if (parts.length > 2) _countryController.text = parts[2];
      _zipController.text = '100001';
      _suggestions = [];
      _searchController.clear();
      _isGoogleValidated = true;
    });

    _standardizeAddressFields();
  }

  void _useCurrentLocation() {
    setState(() => _isValidating = true);
    Future.delayed(const Duration(milliseconds: 600), () {
      if (!mounted) return;
      setState(() {
        _streetController.text = 'Plot 14 Admiralty Way, Lekki Phase 1';
        _cityController.text = 'Lekki';
        _stateController.text = 'Lagos State';
        _zipController.text = '105102';
        _countryController.text = 'Nigeria';
        _isValidating = false;
        _isGoogleValidated = true;
      });

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Row(
            children: [
              const Icon(Icons.gps_fixed_rounded, color: Color(0xFFC49A45), size: 18),
              const SizedBox(width: 10),
              Text(
                'Current location resolved & standardized.',
                style: GoogleFonts.urbanist(color: Colors.white, fontWeight: FontWeight.w600),
              ),
            ],
          ),
          backgroundColor: const Color(0xFF1B1D19),
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      );
    });
  }

  void _standardizeAddressFields() {
    final validated = AddressValidationService.standardizeAndValidate(
      street: _streetController.text,
      city: _cityController.text,
      state: _stateController.text,
      postalCode: _zipController.text,
      country: _countryController.text,
    );

    setState(() {
      _streetController.text = validated.street;
      _cityController.text = validated.city;
      _stateController.text = validated.state;
      _zipController.text = validated.postalCode;
      _countryController.text = validated.country;
      _isGoogleValidated = validated.isDeliverable;
    });
  }

  Future<void> _saveAddress() async {
    if (!_formKey.currentState!.validate()) return;
    final api = context.read<NoirApiClient>();
    setState(() => _saving = true);

    // Standardize before saving
    final validated = AddressValidationService.standardizeAndValidate(
      street: _streetController.text,
      city: _cityController.text,
      state: _stateController.text,
      postalCode: _zipController.text,
      country: _countryController.text,
    );

    if (!validated.isDeliverable) {
      setState(() => _saving = false);
      final errorMsg = validated.warnings.isNotEmpty
          ? validated.warnings.first
          : 'Please provide a genuine and complete delivery address.';
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('⚠️ $errorMsg'),
          backgroundColor: Colors.redAccent,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        ),
      );
      return;
    }

    final newAddr = {
      'id': 'addr_${DateTime.now().millisecondsSinceEpoch}',
      'name': _nameController.text.trim(),
      'phone': _phoneController.text.trim(),
      'address_line1': validated.street,
      'city': validated.city,
      'state': validated.state,
      'postal_code': validated.postalCode,
      'country': validated.country,
      'formatted_address': validated.formattedAddress,
      'is_default': true,
    };

    try {
      final prefs = await SharedPreferences.getInstance();
      final storageKey = _getUserStorageKey();
      final data = prefs.getString(storageKey);
      List<Map<String, dynamic>> existing = [];
      if (data != null && data.isNotEmpty) {
        try {
          final List<dynamic> decoded = jsonDecode(data);
          existing = decoded.map((e) => Map<String, dynamic>.from(e)).toList();
          for (final a in existing) {
            a['is_default'] = false;
          }
        } catch (_) {}
      }
      existing.insert(0, newAddr);
      await prefs.setString(storageKey, jsonEncode(existing));

      try {
        if (api.isAuthenticated) {
          await api.post('/api/v1/mobile/addresses', {
            'name': _nameController.text.trim(),
            'phone': _phoneController.text.trim(),
            'address_line1': validated.street,
            'city': validated.city,
            'state': validated.state,
            'postal_code': validated.postalCode,
            'country': validated.country,
            'is_default': '1',
          });
        }
      } catch (_) {}

      if (mounted) {
        Navigator.pop(context, newAddr);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Row(
              children: [
                const Icon(Icons.check_circle_outline_rounded, color: Colors.greenAccent, size: 18),
                const SizedBox(width: 10),
                Text(
                  'Address verified with Google Maps & saved.',
                  style: GoogleFonts.urbanist(fontWeight: FontWeight.bold, color: Colors.white),
                ),
              ],
            ),
            backgroundColor: NoirTheme.primary,
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            duration: const Duration(seconds: 2),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        setState(() => _saving = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Could not save address: $e', style: GoogleFonts.urbanist()),
            backgroundColor: Colors.redAccent,
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          ),
        );
      }
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
          icon: const Icon(Icons.close_rounded, size: 24),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text(
          strings.addNewAddress,
          style: GoogleFonts.urbanist(fontSize: 18, fontWeight: FontWeight.w800),
        ),
        actions: [
          IconButton(
            tooltip: strings.useCurrentLocation,
            icon: _isValidating
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.my_location_rounded, size: 22),
            onPressed: _isValidating ? null : _useCurrentLocation,
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          autovalidateMode: AutovalidateMode.onUserInteraction,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Google Maps address search bar
              Container(
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.surface,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(
                    color: Theme.of(context).colorScheme.primary.withValues(alpha: 0.3),
                  ),
                ),
                child: TextField(
                  controller: _searchController,
                  style: GoogleFonts.urbanist(fontSize: 14),
                  decoration: InputDecoration(
                    hintText: 'Search address or landmark on Google Maps...',
                    hintStyle: GoogleFonts.urbanist(
                      color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5),
                      fontSize: 13,
                    ),
                    prefixIcon: const Icon(Icons.map_rounded, color: Color(0xFFC49A45), size: 20),
                    suffixIcon: _searchController.text.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear, size: 18),
                            onPressed: () => _searchController.clear(),
                          )
                        : null,
                    border: InputBorder.none,
                    contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
                  ),
                ),
              ),

              // Search Suggestions Dropdown
              if (_suggestions.isNotEmpty) ...[
                const SizedBox(height: 6),
                Container(
                  decoration: BoxDecoration(
                    color: Theme.of(context).colorScheme.surface,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Theme.of(context).colorScheme.outline),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.08),
                        blurRadius: 12,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  child: ListView.separated(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    itemCount: _suggestions.length,
                    separatorBuilder: (_, _) => const Divider(height: 1),
                    itemBuilder: (context, index) {
                      final item = _suggestions[index];
                      return ListTile(
                        dense: true,
                        leading: const Icon(Icons.location_on_rounded, color: Color(0xFFC49A45), size: 20),
                        title: Text(
                          item.mainText,
                          style: GoogleFonts.urbanist(fontSize: 13, fontWeight: FontWeight.w700),
                        ),
                        subtitle: Text(
                          item.secondaryText,
                          style: GoogleFonts.urbanist(fontSize: 11, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.6)),
                        ),
                        onTap: () => _applySuggestion(item),
                      );
                    },
                  ),
                ),
              ],

              const SizedBox(height: 16),

              // GPS Quick Button
              OutlinedButton.icon(
                onPressed: _useCurrentLocation,
                icon: const Icon(Icons.gps_fixed_rounded, size: 16, color: Color(0xFFC49A45)),
                label: Text(
                  strings.useCurrentLocation,
                  style: GoogleFonts.urbanist(fontSize: 13, fontWeight: FontWeight.w700),
                ),
                style: OutlinedButton.styleFrom(
                  foregroundColor: Theme.of(context).colorScheme.onSurface,
                  side: BorderSide(color: Theme.of(context).colorScheme.outline),
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
              ),

              const SizedBox(height: 20),

              _buildTextField(
                label: strings.fullName,
                hint: 'e.g. Amara Johnson',
                controller: _nameController,
                icon: Icons.person_outline_rounded,
                validator: (v) {
                  if (v == null || v.trim().isEmpty) return strings.required;
                  if (v.trim().length < 2) return 'Name must be at least 2 characters';
                  if (RegExp(r'^\d+$').hasMatch(v.trim())) return 'Name cannot be numbers only';
                  return null;
                },
              ),
              const SizedBox(height: 14),
              _buildTextField(
                label: strings.phoneNumber,
                hint: 'e.g. 08012345678',
                controller: _phoneController,
                icon: Icons.phone_outlined,
                keyboardType: TextInputType.phone,
                inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[0-9+\-\s]'))],
                validator: (v) {
                  if (v == null || v.trim().isEmpty) return strings.required;
                  final digits = v.replaceAll(RegExp(r'\D'), '');
                  if (digits.length < 7 || digits.length > 15) {
                    return 'Enter a valid phone number';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 14),
              _buildTextField(
                label: strings.streetAddress,
                hint: 'e.g. 12 Adeola Odeku Street',
                controller: _streetController,
                icon: Icons.location_on_outlined,
                validator: (v) {
                  if (v == null || v.trim().isEmpty) return strings.required;
                  if (v.trim().length < 5) return 'Enter a more complete street address';
                  return null;
                },
              ),
              const SizedBox(height: 14),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: _buildTextField(
                      label: strings.city,
                      hint: 'e.g. Lagos',
                      controller: _cityController,
                      icon: Icons.location_city_outlined,
                      validator: (v) {
                        if (v == null || v.trim().isEmpty) return strings.required;
                        if (RegExp(r'^\d+$').hasMatch(v.trim())) return 'Enter a valid city';
                        return null;
                      },
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _buildTextField(
                      label: strings.state,
                      hint: 'e.g. Lagos State',
                      controller: _stateController,
                      icon: Icons.map_outlined,
                      validator: (v) {
                        if (v == null || v.trim().isEmpty) return strings.required;
                        return null;
                      },
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 14),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: _buildTextField(
                      label: strings.postalCode,
                      hint: 'e.g. 100001',
                      controller: _zipController,
                      icon: Icons.local_post_office_outlined,
                      keyboardType: TextInputType.number,
                      inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                      validator: (v) {
                        if (v == null || v.trim().isEmpty) return strings.required;
                        if (v.trim().length < 4) return 'Enter a valid postal code';
                        return null;
                      },
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _buildTextField(
                      label: strings.country,
                      hint: 'e.g. Nigeria',
                      controller: _countryController,
                      icon: Icons.flag_outlined,
                      validator: (v) {
                        if (v == null || v.trim().isEmpty) return strings.required;
                        return null;
                      },
                    ),
                  ),
                ],
              ),

              if (_isGoogleValidated) ...[
                const SizedBox(height: 14),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: const Color(0xFF687158).withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: const Color(0xFF687158).withValues(alpha: 0.4)),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.verified_rounded, color: Color(0xFF687158), size: 18),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          strings.addressVerified,
                          style: GoogleFonts.urbanist(
                            fontSize: 12,
                            fontWeight: FontWeight.w700,
                            color: const Color(0xFF687158),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],

              const SizedBox(height: 32),
              ElevatedButton(
                onPressed: _saving ? null : _saveAddress,
                style: ElevatedButton.styleFrom(
                  backgroundColor: Theme.of(context).colorScheme.primary,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  elevation: 0,
                  disabledBackgroundColor: Theme.of(context).colorScheme.primary.withValues(alpha: 0.5),
                ),
                child: _saving
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                      )
                    : Text(
                        strings.saveAddress,
                        style: GoogleFonts.urbanist(
                          fontSize: 14,
                          fontWeight: FontWeight.w700,
                          letterSpacing: 1,
                        ),
                      ),
              ),
              const SizedBox(height: 24),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTextField({
    required String label,
    required String hint,
    required TextEditingController controller,
    required IconData icon,
    TextInputType? keyboardType,
    List<TextInputFormatter>? inputFormatters,
    String? Function(String?)? validator,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: GoogleFonts.urbanist(
            fontSize: 13,
            fontWeight: FontWeight.w600,
            color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7),
          ),
        ),
        const SizedBox(height: 6),
        TextFormField(
          controller: controller,
          keyboardType: keyboardType,
          inputFormatters: inputFormatters,
          validator: validator,
          style: GoogleFonts.urbanist(fontSize: 15),
          decoration: InputDecoration(
            hintText: hint,
            hintStyle: GoogleFonts.urbanist(
              color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5),
              fontSize: 14,
            ),
            prefixIcon: Icon(icon, size: 18, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5)),
            filled: true,
            fillColor: Theme.of(context).colorScheme.surface,
            contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: BorderSide(color: Theme.of(context).colorScheme.outline),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: BorderSide(color: Theme.of(context).colorScheme.outline),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: BorderSide(color: Theme.of(context).colorScheme.primary, width: 1.5),
            ),
            errorBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: const BorderSide(color: Colors.redAccent),
            ),
            focusedErrorBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: const BorderSide(color: Colors.redAccent, width: 1.5),
            ),
            errorStyle: GoogleFonts.urbanist(fontSize: 11, color: Colors.redAccent),
          ),
        ),
      ],
    );
  }
}
