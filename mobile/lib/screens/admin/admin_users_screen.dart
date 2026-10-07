import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

import '../../api_client.dart';

class AdminUsersScreen extends StatefulWidget {
  const AdminUsersScreen({super.key});

  @override
  State<AdminUsersScreen> createState() => _AdminUsersScreenState();
}

class _AdminUsersScreenState extends State<AdminUsersScreen> {
  bool _loading = true;
  List<dynamic> _users = [];
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadUsers();
  }

  Future<void> _loadUsers() async {
    try {
      final api = context.read<NoirApiClient>();
      // Fallback direct http request as getAdminUsers might not exist yet
      final url = Uri.parse('${api.baseUrl}/api/admin/users');
      final headers = <String, String>{
        'Accept': 'application/json',
      };
      
      final String? token = api.token;
      if (token != null && token.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }

      final response = await http.get(url, headers: headers);
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
         if (mounted) {
          setState(() {
            _users = data['data'] ?? [];
            _loading = false;
          });
        }
      } else {
         if (mounted) {
          setState(() {
            _error = "Error loading users: ${response.statusCode}";
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
          'Manage Users',
          style: GoogleFonts.urbanist(fontWeight: FontWeight.w700, fontSize: 18),
        ),
        actions: [
          IconButton(
            icon: Icon(Icons.refresh_rounded),
            onPressed: () {
              setState(() => _loading = true);
              _loadUsers();
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
                          _loadUsers();
                        },
                        child: Text('RETRY'),
                      ),
                    ],
                  ),
                )
              : ListView.separated(
                  padding: const EdgeInsets.all(16),
                  itemCount: _users.length,
                  separatorBuilder: (_, _) => const SizedBox(height: 12),
                  itemBuilder: (context, index) {
                    final user = _users[index];
                    return Container(
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.03),
                            blurRadius: 8,
                            offset: const Offset(0, 2),
                          ),
                        ],
                      ),
                      child: ListTile(
                        leading: CircleAvatar(
                          backgroundColor: Theme.of(context).colorScheme.secondary.withValues(alpha: 0.1),
                          child: Icon(Icons.person, color: Theme.of(context).colorScheme.secondary),
                        ),
                        title: Text(
                          '${user['first_name'] ?? ''} ${user['last_name'] ?? ''}',
                          style: GoogleFonts.urbanist(fontWeight: FontWeight.w700),
                        ),
                        subtitle: Text(
                          user['email'] ?? '',
                          style: GoogleFonts.urbanist(fontSize: 13, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.7)),
                        ),
                        trailing: Icon(Icons.chevron_right, color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.5)),
                        onTap: () {
                           // Navigate to user details
                        },
                      ),
                    );
                  },
                ),
    );
  }
}
