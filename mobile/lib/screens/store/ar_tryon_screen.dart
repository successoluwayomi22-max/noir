import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class ARTryOnScreen extends StatelessWidget {
  const ARTryOnScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: IconThemeData(color: Colors.white),
        title: Text('AR Try-On', style: GoogleFonts.urbanist(color: Colors.white)),
      ),
      extendBodyBehindAppBar: true,
      body: Stack(
        fit: StackFit.expand,
        children: [
          // Placeholder for Camera/AR View
          Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.camera_alt_outlined, color: Colors.white54, size: 64),
                SizedBox(height: 16),
                Text(
                  'Camera Feed Placeholder\n(Requires 3D .glb models)',
                  textAlign: TextAlign.center,
                  style: GoogleFonts.urbanist(color: Colors.white54, fontSize: 18),
                ),
              ],
            ),
          ),
          
          // Bottom Controls
          Positioned(
            bottom: 40,
            left: 0,
            right: 0,
            child: Center(
              child: ElevatedButton.icon(
                onPressed: () {},
                icon: Icon(Icons.camera),
                label: Text('Take Snapshot'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.white,
                  foregroundColor: Colors.black,
                  padding: EdgeInsets.symmetric(horizontal: 32, vertical: 16),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
