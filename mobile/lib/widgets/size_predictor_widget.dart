import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../api_client.dart';
import '../models/product.dart';
import '../theme.dart';

class SizePredictorWidget extends StatefulWidget {
  final Product product;
  final NoirApiClient apiClient;
  final Function(String) onSizeSelected;

  const SizePredictorWidget({
    super.key,
    required this.product,
    required this.apiClient,
    required this.onSizeSelected,
  });

  @override
  State<SizePredictorWidget> createState() => _SizePredictorWidgetState();
}

class _SizePredictorWidgetState extends State<SizePredictorWidget> {
  // Dimensions
  double _height = 175.0; // cm
  double _weight = 70.0; // kg
  double _chest = 98.0; // cm
  double _waist = 80.0; // cm
  double _hip = 95.0; // cm

  String _fitPreference = 'Tailored'; // Relaxed, Tailored, Oversized
  
  bool _isLoading = false;
  Map<String, dynamic>? _prediction;
  String? _error;

  Future<void> _runPredictor() async {
    setState(() {
      _isLoading = true;
      _error = null;
      _prediction = null;
    });
    
    try {
      final data = await widget.apiClient.predictSize(
        productId: widget.product.id,
        height: _height,
        weight: _weight,
        chest: _chest,
        waist: _waist,
        hip: _hip,
        fitPreference: _fitPreference,
      );
      
      setState(() {
        _prediction = data;
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString().replaceAll('Exception: ', '');
        _isLoading = false;
      });
    }
  }

  Widget _buildSliderRow(String label, double value, double min, double max, String unit, ValueChanged<double> onChanged) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(label, style: GoogleFonts.urbanist(fontWeight: FontWeight.bold, fontSize: 13, color: Colors.grey[300])),
            Text('${value.toStringAsFixed(1)} $unit', style: GoogleFonts.urbanist(fontWeight: FontWeight.bold, fontSize: 13, color: NoirTheme.primary)),
          ],
        ),
        SliderTheme(
          data: SliderThemeData(
            trackHeight: 2,
            activeTrackColor: NoirTheme.primary,
            inactiveTrackColor: Colors.white12,
            thumbColor: NoirTheme.primary,
            overlayColor: NoirTheme.primary.withValues(alpha: 0.1),
            thumbShape: const RoundSliderThumbShape(enabledThumbRadius: 6),
          ),
          child: Slider(
            value: value,
            min: min,
            max: max,
            onChanged: onChanged,
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_prediction != null) {
      return _buildResultView();
    }

    return Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSliderRow('Height', _height, 140, 220, 'cm', (v) => setState(() => _height = v)),
        const SizedBox(height: 8),
        _buildSliderRow('Weight', _weight, 40, 150, 'kg', (v) => setState(() => _weight = v)),
        const SizedBox(height: 8),
        _buildSliderRow('Chest / Bust', _chest, 70, 140, 'cm', (v) => setState(() => _chest = v)),
        const SizedBox(height: 8),
        _buildSliderRow('Waist', _waist, 50, 130, 'cm', (v) => setState(() => _waist = v)),
        const SizedBox(height: 8),
        _buildSliderRow('Hip', _hip, 70, 140, 'cm', (v) => setState(() => _hip = v)),
        
        const SizedBox(height: 16),
        Text('Fit Preference', style: GoogleFonts.urbanist(fontWeight: FontWeight.bold, fontSize: 13, color: Colors.grey[300])),
        const SizedBox(height: 10),
        Row(
          children: ['Tailored', 'Relaxed', 'Oversized'].map((fit) {
            final isSelected = _fitPreference == fit;
            return Padding(
              padding: const EdgeInsets.only(right: 8.0),
              child: ChoiceChip(
                label: Text(fit, style: GoogleFonts.urbanist(
                  fontSize: 12, 
                  fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
                  color: isSelected ? Colors.black : Colors.white
                )),
                selected: isSelected,
                selectedColor: NoirTheme.primary,
                backgroundColor: Colors.white10,
                onSelected: (bool selected) {
                  if (selected) setState(() => _fitPreference = fit);
                },
              ),
            );
          }).toList(),
        ),
        
        const SizedBox(height: 24),
        
        if (_error != null)
          Padding(
            padding: const EdgeInsets.only(bottom: 16),
            child: Text(_error!, style: const TextStyle(color: Colors.redAccent, fontSize: 13)),
          ),
          
        SizedBox(
          width: double.infinity,
          height: 50,
          child: ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: NoirTheme.primary,
              foregroundColor: Colors.black,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(0)),
            ),
            onPressed: _isLoading ? null : _runPredictor,
            child: _isLoading 
                ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.black))
                : Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.auto_awesome, size: 18),
                      const SizedBox(width: 8),
                      Text('RUN AI PREDICTOR', style: GoogleFonts.urbanist(fontWeight: FontWeight.w900, fontSize: 13, letterSpacing: 1)),
                    ],
                  ),
          ),
        ),
      ],
    );
  }

  Widget _buildResultView() {
    final size = _prediction!['recommended_size'];
    final confidence = _prediction!['confidence_score'];
    final notes = _prediction!['bespoke_notes'];
    
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(Icons.check_circle_outline, color: NoirTheme.primary, size: 56),
        const SizedBox(height: 16),
        Text('AI Recommendation', style: GoogleFonts.urbanist(fontSize: 14, color: Colors.grey)),
        const SizedBox(height: 4),
        Text(size, style: GoogleFonts.playfairDisplay(fontSize: 42, fontWeight: FontWeight.bold, color: Colors.white)),
        const SizedBox(height: 8),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
          decoration: BoxDecoration(
            color: NoirTheme.primary.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: NoirTheme.primary.withValues(alpha: 0.5)),
          ),
          child: Text('Confidence: $confidence', style: GoogleFonts.urbanist(color: NoirTheme.primary, fontWeight: FontWeight.bold, fontSize: 12)),
        ),
        const SizedBox(height: 24),
        if (notes != null)
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.05),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(Icons.info_outline, size: 18, color: Colors.white70),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(notes, style: GoogleFonts.urbanist(fontSize: 13, color: Colors.white70, height: 1.5)),
                )
              ],
            ),
          ),
        const SizedBox(height: 24),
        SizedBox(
          width: double.infinity,
          height: 50,
          child: ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: NoirTheme.primary,
              foregroundColor: Colors.black,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(0)),
            ),
            onPressed: () {
              widget.onSizeSelected(size);
              Navigator.pop(context);
            },
            child: Text('APPLY SIZE $size', style: GoogleFonts.urbanist(fontWeight: FontWeight.w900, fontSize: 13, letterSpacing: 1)),
          ),
        ),
        const SizedBox(height: 12),
        TextButton(
          onPressed: () => setState(() => _prediction = null),
          child: Text('Recalculate', style: GoogleFonts.urbanist(color: Colors.grey, fontSize: 13, fontWeight: FontWeight.bold)),
        )
      ],
    );
  }
}
