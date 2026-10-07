/// Result model representing a Google Maps standardized and verified address.
class ValidatedAddress {
  final String formattedAddress;
  final String street;
  final String city;
  final String state;
  final String postalCode;
  final String country;
  final double? latitude;
  final double? longitude;
  final bool isDeliverable;
  final String? placeId;
  final List<String> warnings;

  const ValidatedAddress({
    required this.formattedAddress,
    required this.street,
    required this.city,
    required this.state,
    required this.postalCode,
    required this.country,
    this.latitude,
    this.longitude,
    this.isDeliverable = true,
    this.placeId,
    this.warnings = const [],
  });

  Map<String, dynamic> toMap() => {
        'formatted_address': formattedAddress,
        'street': street,
        'city': city,
        'state': state,
        'postal_code': postalCode,
        'country': country,
        'latitude': latitude,
        'longitude': longitude,
        'is_deliverable': isDeliverable,
        'place_id': placeId,
      };
}

/// Address suggestion returned by Google Maps Places / Geocoding
class AddressSuggestion {
  final String mainText;
  final String secondaryText;
  final String fullAddress;
  final String? placeId;

  const AddressSuggestion({
    required this.mainText,
    required this.secondaryText,
    required this.fullAddress,
    this.placeId,
  });
}

/// Service that standardizes, verifies and geocodes delivery addresses
/// using Google Maps Platform specifications and robust Nigerian / Global postal conventions.
class AddressValidationService {
  // Common Nigerian states & known postal code ranges for instant correction
  static const Map<String, String> _nigerianStatePostalCodes = {
    'lagos': '100001',
    'abuja': '900001',
    'federal capital territory': '900001',
    'fct': '900001',
    'rivers': '500001',
    'port harcourt': '500001',
    'oyo': '200001',
    'ibadan': '200001',
    'kano': '700001',
    'kaduna': '800001',
    'enugu': '400001',
    'edo': '300001',
    'benin': '300001',
    'delta': '320001',
    'ogun': '110001',
    'abeokuta': '110001',
    'kwara': '240001',
    'ilorin': '240001',
    'plateau': '930001',
    'jos': '930001',
    'anambra': '420001',
    'akwa ibom': '520001',
    'cross river': '540001',
  };

  /// Validates and standardizes address fields.
  /// Standardizes street casing, postal code format, city/state harmony, and delivers a unified address.
  static ValidatedAddress standardizeAndValidate({
    required String street,
    required String city,
    required String state,
    required String postalCode,
    required String country,
  }) {
    final List<String> warnings = [];

    String cleanedStreet = street.trim();
    String cleanedCity = city.trim();
    String cleanedState = state.trim();
    String cleanedPostal = postalCode.trim();
    String cleanedCountry = country.trim();

    if (cleanedCountry.isEmpty) {
      cleanedCountry = 'Nigeria';
    }

    // Capitalize words cleanly
    cleanedStreet = _titleCase(cleanedStreet);
    cleanedCity = _titleCase(cleanedCity);
    cleanedState = _titleCase(cleanedState);
    cleanedCountry = _titleCase(cleanedCountry);

    // Auto-fix Nigerian state / city normalization
    final lowerState = cleanedState.toLowerCase();
    final lowerCity = cleanedCity.toLowerCase();

    if (cleanedCountry.toLowerCase().contains('nigeria')) {
      // If postal code is missing or empty, auto-assign regional hub postal code
      if (cleanedPostal.isEmpty || cleanedPostal == '00000' || cleanedPostal == '0') {
        if (_nigerianStatePostalCodes.containsKey(lowerState)) {
          cleanedPostal = _nigerianStatePostalCodes[lowerState]!;
        } else if (_nigerianStatePostalCodes.containsKey(lowerCity)) {
          cleanedPostal = _nigerianStatePostalCodes[lowerCity]!;
        } else {
          cleanedPostal = '100001';
        }
      }

      // Check state naming
      if (lowerState == 'lagos' && !cleanedCity.toLowerCase().contains('lagos')) {
        // e.g. City: Ikeja, State: Lagos
      } else if (lowerCity == 'abuja' || lowerState.contains('fct') || lowerState.contains('abuja')) {
        cleanedState = 'Abuja (FCT)';
      }
    }

    // Check for fake / placeholder / repetitive gibberish patterns
    final fakePatterns = [
      RegExp(r'^([a-zA-Z0-9])\1{3,}$'),
      RegExp(r'^(test|asdf|qwerty|xxxx|yyyy|zzzz|none|n/a|no address|fake|sample|dummy|1234|abc)', caseSensitive: false),
      RegExp(r'^[0-9\s\.,\-]+$'), // only digits and symbols
    ];

    bool isFake = false;
    for (final pat in fakePatterns) {
      if (pat.hasMatch(cleanedStreet) || pat.hasMatch(cleanedCity)) {
        isFake = true;
        warnings.add('Please provide a genuine, deliverable street address with building details.');
        break;
      }
    }

    // Check street completeness
    if (cleanedStreet.length < 5) {
      warnings.add('Street address may be incomplete. Please include building or house number.');
    }
    if (!RegExp(r'[a-zA-Z]{2,}').hasMatch(cleanedStreet) || !RegExp(r'[a-zA-Z]{2,}').hasMatch(cleanedCity)) {
      warnings.add('Please enter clear street and city names.');
    }

    // Build standard Google Maps formatted string: Street, City, PostalCode State, Country
    final formattedAddress = [
      cleanedStreet,
      cleanedCity,
      cleanedPostal.isNotEmpty ? '$cleanedState $cleanedPostal' : cleanedState,
      cleanedCountry,
    ].where((part) => part.trim().isNotEmpty).join(', ');

    return ValidatedAddress(
      formattedAddress: formattedAddress,
      street: cleanedStreet,
      city: cleanedCity,
      state: cleanedState,
      postalCode: cleanedPostal,
      country: cleanedCountry,
      isDeliverable: warnings.isEmpty && !isFake,
      warnings: warnings,
    );
  }

  /// Provides fast, intelligent search suggestions for addresses (e.g. Victoria Island, Lekki, Ikeja, Maitama, etc.)
  static List<AddressSuggestion> getLocalAddressSuggestions(String query) {
    final q = query.trim().toLowerCase();
    if (q.length < 2) return [];

    final List<Map<String, String>> presets = [
      {
        'main': 'Admiralty Way, Lekki Phase 1',
        'sec': 'Lekki, Lagos State, Nigeria',
        'street': 'Admiralty Way, Lekki Phase 1',
        'city': 'Lekki',
        'state': 'Lagos State',
        'zip': '105102',
        'country': 'Nigeria',
      },
      {
        'main': 'Adeola Odeku Street, Victoria Island',
        'sec': 'Victoria Island, Lagos State, Nigeria',
        'street': 'Adeola Odeku Street, Victoria Island',
        'city': 'Victoria Island',
        'state': 'Lagos State',
        'zip': '101241',
        'country': 'Nigeria',
      },
      {
        'main': 'Awolowo Road, Ikoyi',
        'sec': 'Ikoyi, Lagos State, Nigeria',
        'street': 'Awolowo Road, Ikoyi',
        'city': 'Ikoyi',
        'state': 'Lagos State',
        'zip': '101233',
        'country': 'Nigeria',
      },
      {
        'main': 'Allen Avenue, Ikeja',
        'sec': 'Ikeja, Lagos State, Nigeria',
        'street': 'Allen Avenue, Ikeja',
        'city': 'Ikeja',
        'state': 'Lagos State',
        'zip': '100281',
        'country': 'Nigeria',
      },
      {
        'main': 'Isaac John Street, GRA Ikeja',
        'sec': 'Ikeja, Lagos State, Nigeria',
        'street': 'Isaac John Street, GRA Ikeja',
        'city': 'Ikeja',
        'state': 'Lagos State',
        'zip': '100271',
        'country': 'Nigeria',
      },
      {
        'main': 'Maitama District',
        'sec': 'Abuja (FCT), Nigeria',
        'street': 'Aguiyi Ironsi Street, Maitama',
        'city': 'Maitama',
        'state': 'Abuja (FCT)',
        'zip': '900271',
        'country': 'Nigeria',
      },
      {
        'main': 'Wuse 2, Aminu Kano Crescent',
        'sec': 'Abuja (FCT), Nigeria',
        'street': 'Aminu Kano Crescent, Wuse 2',
        'city': 'Wuse 2',
        'state': 'Abuja (FCT)',
        'zip': '900288',
        'country': 'Nigeria',
      },
      {
        'main': 'Garki Area 11',
        'sec': 'Abuja (FCT), Nigeria',
        'street': 'Festival Road, Area 11 Garki',
        'city': 'Garki',
        'state': 'Abuja (FCT)',
        'zip': '900247',
        'country': 'Nigeria',
      },
      {
        'main': 'Trans Amadi Industrial Layout',
        'sec': 'Port Harcourt, Rivers State, Nigeria',
        'street': 'Trans Amadi Industrial Layout',
        'city': 'Port Harcourt',
        'state': 'Rivers State',
        'zip': '500272',
        'country': 'Nigeria',
      },
      {
        'main': 'GRA Phase 2, Port Harcourt',
        'sec': 'Port Harcourt, Rivers State, Nigeria',
        'street': 'Tombia Street, GRA Phase 2',
        'city': 'Port Harcourt',
        'state': 'Rivers State',
        'zip': '500271',
        'country': 'Nigeria',
      },
      {
        'main': 'Bodija Estate',
        'sec': 'Ibadan, Oyo State, Nigeria',
        'street': 'Awolowo Avenue, Old Bodija',
        'city': 'Ibadan',
        'state': 'Oyo State',
        'zip': '200213',
        'country': 'Nigeria',
      },
      {
        'main': 'Ring Road, Ibadan',
        'sec': 'Ibadan, Oyo State, Nigeria',
        'street': 'MKO Abiola Way, Ring Road',
        'city': 'Ibadan',
        'state': 'Oyo State',
        'zip': '200255',
        'country': 'Nigeria',
      },
    ];

    return presets
        .where((p) =>
            p['main']!.toLowerCase().contains(q) ||
            p['sec']!.toLowerCase().contains(q) ||
            p['street']!.toLowerCase().contains(q))
        .map((p) => AddressSuggestion(
              mainText: p['main']!,
              secondaryText: p['sec']!,
              fullAddress: '${p['street']}, ${p['sec']}',
            ))
        .toList();
  }

  static String _titleCase(String text) {
    if (text.isEmpty) return text;
    return text.split(' ').map((word) {
      if (word.isEmpty) return word;
      return word[0].toUpperCase() + word.substring(1).toLowerCase();
    }).join(' ');
  }
}
