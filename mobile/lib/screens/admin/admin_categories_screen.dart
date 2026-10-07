import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

import '../../api_client.dart';

class AdminCategoriesScreen extends StatefulWidget {
  const AdminCategoriesScreen({super.key});

  @override
  State<AdminCategoriesScreen> createState() => _AdminCategoriesScreenState();
}

class _AdminCategoriesScreenState extends State<AdminCategoriesScreen> {
  bool _loading = true;
  List<dynamic> _categories = [];
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadCategories();
  }

  Future<void> _loadCategories() async {
    try {
      final api = context.read<NoirApiClient>();
      
      final url = Uri.parse('${api.baseUrl}/api/categories');
      
      final headers = {
        'Accept': 'application/json',
      };
      
      final response = await http.get(url, headers: headers);
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
         if (mounted) {
          setState(() {
            _categories = data['data'] ?? data ?? [];
            _loading = false;
          });
        }
      } else {
         if (mounted) {
          setState(() {
            _error = "Error loading categories: ${response.statusCode}";
            _loading = false;
          });
        }
      }

    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e.toString().replaceAll('Exception: ', '');
          _loading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        backgroundColor: Theme.of(context).colorScheme.primary,
        foregroundColor: Colors.white,
        elevation: 0,
        title: Text(
          'Manage Categories',
          style: GoogleFonts.urbanist(fontWeight: FontWeight.w700, fontSize: 18),
        ),
        actions: [
          IconButton(
            icon: Icon(Icons.refresh_rounded),
            onPressed: () {
              setState(() => _loading = true);
              _loadCategories();
            },
          ),
        ],
      ),
      body: _loading
          ? Center(child: CircularProgressIndicator(color: Theme.of(context).colorScheme.secondary))
          : _error != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.error_outline, size: 48, color: Theme.of(context).colorScheme.error),
                      SizedBox(height: 16),
                      Text('Error: $_error', style: GoogleFonts.urbanist()),
                      SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: () {
                          setState(() => _loading = true);
                          _loadCategories();
                        },
                        child: Text('RETRY'),
                      ),
                    ],
                  ),
                )
              : ListView.separated(
                  padding: EdgeInsets.all(16),
                  itemCount: _categories.length,
                  separatorBuilder: (_, _) => const SizedBox(height: 12),
                  itemBuilder: (context, index) {
                    final category = _categories[index];
                    return Container(
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.03),
                            blurRadius: 8,
                            offset: Offset(0, 2),
                          ),
                        ],
                      ),
                      child: ListTile(
                        leading: CircleAvatar(
                          backgroundColor: Theme.of(context).colorScheme.secondary.withValues(alpha: 0.1),
                          child: Icon(Icons.category, color: Theme.of(context).colorScheme.secondary),
                        ),
                        title: Text(
                          category['name'] ?? '',
                          style: GoogleFonts.urbanist(fontWeight: FontWeight.w700),
                        ),
                        subtitle: Text(
                          category['slug'] ?? '',
                          style: GoogleFonts.urbanist(fontSize: 13, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7)),
                        ),
                        trailing: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            IconButton(
                              icon: Icon(Icons.edit_outlined, color: Theme.of(context).colorScheme.primary),
                              onPressed: () {},
                            ),
                            IconButton(
                              icon: Icon(Icons.delete_outline, color: Theme.of(context).colorScheme.error),
                              onPressed: () {},
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
      floatingActionButton: FloatingActionButton(
        backgroundColor: Theme.of(context).colorScheme.primary,
        onPressed: () {
          // Add Category
        },
        child: Icon(Icons.add, color: Colors.white),
      ),
    );
  }
}
