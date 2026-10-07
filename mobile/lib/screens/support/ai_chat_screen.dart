import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../ai/ai_chat_screen.dart';
import '../../providers/settings_provider.dart';

export '../ai/ai_chat_screen.dart';

class SupportAiChatScreen extends StatelessWidget {
  const SupportAiChatScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final storeName = context.watch<SettingsProvider>().brandName;
    return AiChatScreen(
      title: '$storeName Client Concierge',
    );
  }
}
