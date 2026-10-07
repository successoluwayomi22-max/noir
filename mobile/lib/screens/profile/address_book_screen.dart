import 'package:flutter/material.dart';

class AddressBookScreen extends StatelessWidget {
  const AddressBookScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Address Book')),
      body: ListView(
        padding: EdgeInsets.all(16),
        children: [
          _buildAddressCard(context, 'Home', '123 Noir Street, Lagos, Nigeria', true),
          _buildAddressCard(context, 'Office', '45 Business Ave, Abuja, Nigeria', false),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () {},
        child: Icon(Icons.add),
      ),
    );
  }

  Widget _buildAddressCard(BuildContext context, String title, String address, bool isDefault) {
    return Card(
      margin: EdgeInsets.only(bottom: 16),
      child: ListTile(
        leading: Icon(Icons.location_on),
        title: Text(title, style: TextStyle(fontWeight: FontWeight.bold)),
        subtitle: Text(address),
        trailing: isDefault ? Chip(label: Text('Default')) : null,
      ),
    );
  }
}
