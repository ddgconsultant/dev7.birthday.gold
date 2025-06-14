#!/usr/bin/env python3
"""
Automatic Logo Converter (Simple Version)
Converts any image file to transparent PNG with white-friendly format
Place logos in this CONVERT folder and run this script
Processed logos will be moved to DONE folder
"""

import os
import shutil
from datetime import datetime

# Setup paths - works on both Windows and Linux
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
CONVERT_DIR = SCRIPT_DIR
DONE_DIR = os.path.join(SCRIPT_DIR, 'DONE')
OUTPUT_DIR = os.path.abspath(os.path.join(SCRIPT_DIR, '..', 'bwpng'))

# Create directories if they don't exist
os.makedirs(DONE_DIR, exist_ok=True)
os.makedirs(OUTPUT_DIR, exist_ok=True)

# Try to import required libraries
try:
    from PIL import Image
except ImportError:
    print("PIL/Pillow not found. Installing...")
    os.system("pip3 install --user Pillow")
    try:
        from PIL import Image
    except ImportError:
        print("ERROR: Could not import PIL. Please install manually:")
        print("  pip3 install --user Pillow")
        print("  or")
        print("  sudo apt-get install python3-pil")
        exit(1)

# Try to import cairosvg for SVG support
try:
    import cairosvg
    SVG_SUPPORT = True
except ImportError:
    SVG_SUPPORT = False
    print("Note: SVG support not available. Install cairosvg for SVG conversion.")
    print("  pip3 install --user cairosvg")

# Configuration
TARGET_HEIGHT = 55
MIN_WIDTH = 80
SUPPORTED_FORMATS = ['.png', '.jpg', '.jpeg', '.gif', '.bmp', '.webp']
if SVG_SUPPORT:
    SUPPORTED_FORMATS.append('.svg')
SUPPORTED_FORMATS = tuple(SUPPORTED_FORMATS)

def convert_logo(input_path, output_path):
    """Convert a logo to transparent PNG format"""
    try:
        # Handle SVG files
        if input_path.lower().endswith('.svg'):
            if not SVG_SUPPORT:
                return False, "SVG support not available. Install cairosvg."
            
            # Convert SVG to PNG in memory
            import io
            png_data = cairosvg.svg2png(url=input_path, output_height=TARGET_HEIGHT*4)
            img = Image.open(io.BytesIO(png_data))
        else:
            # Open regular image
            img = Image.open(input_path)
        
        original_mode = img.mode
        
        # Convert to RGBA if not already
        if img.mode != 'RGBA':
            img = img.convert('RGBA')
        
        # Get image data
        data = img.getdata()
        
        # Process pixels
        new_data = []
        for item in data:
            # Ensure we have RGBA
            if len(item) == 4:
                r, g, b, a = item
            else:
                r, g, b = item[0], item[1], item[2]
                a = 255
            
            # If already transparent, keep it
            if a < 10:
                new_data.append((255, 255, 255, 0))
            # If pixel is white or near-white, make transparent
            elif r > 250 and g > 250 and b > 250:
                new_data.append((255, 255, 255, 0))
            # If pixel has color (not grayscale), convert to black
            elif abs(r - g) > 15 or abs(g - b) > 15 or abs(r - b) > 15:
                new_data.append((0, 0, 0, 255))
            # If it's a dark gray/black, keep as black
            elif r < 100 and g < 100 and b < 100:
                new_data.append((0, 0, 0, 255))
            # Light grays -> transparent
            elif r > 200 and g > 200 and b > 200:
                new_data.append((255, 255, 255, 0))
            # Mid grays -> decide based on average
            else:
                avg = (r + g + b) / 3
                if avg < 150:
                    new_data.append((0, 0, 0, 255))
                else:
                    new_data.append((255, 255, 255, 0))
        
        # Update image data
        img.putdata(new_data)
        
        # Calculate new dimensions
        aspect_ratio = img.width / img.height
        
        # Check if logo is square/circular (aspect ratio close to 1)
        is_square = 0.9 <= aspect_ratio <= 1.1
        
        if is_square:
            # For square/circular logos, maintain square dimensions
            # Use TARGET_HEIGHT for both width and height
            new_width = TARGET_HEIGHT
            new_height = TARGET_HEIGHT
        else:
            # For other logos, maintain aspect ratio with minimum width
            new_width = int(TARGET_HEIGHT * aspect_ratio)
            new_height = TARGET_HEIGHT
            
            # Apply minimum width constraint only for non-square logos
            if new_width < MIN_WIDTH:
                new_width = MIN_WIDTH
        
        # Resize image
        img = img.resize((new_width, new_height), Image.Resampling.LANCZOS)
        
        # Save as PNG with transparency
        img.save(output_path, 'PNG', optimize=True)
        
        return True, f"Converted successfully: {new_width}x{TARGET_HEIGHT}"
        
    except Exception as e:
        return False, f"Error: {str(e)}"

def main():
    """Main conversion process"""
    print(f"Logo Converter - {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print("=" * 50)
    print(f"Scanning: {CONVERT_DIR}")
    print(f"Output to: {OUTPUT_DIR}")
    print(f"Processed files moved to: {DONE_DIR}")
    print("=" * 50)
    
    # Find all image files
    files_found = []
    for filename in os.listdir(CONVERT_DIR):
        if filename.lower().endswith(SUPPORTED_FORMATS):
            files_found.append(filename)
    
    if not files_found:
        print("No image files found to convert.")
        return
    
    print(f"\nFound {len(files_found)} image(s) to process:")
    
    # Process each file
    success_count = 0
    for filename in files_found:
        input_path = os.path.join(CONVERT_DIR, filename)
        
        # Generate output filename (always .png)
        base_name = os.path.splitext(filename)[0]
        output_filename = base_name + '.png'
        output_path = os.path.join(OUTPUT_DIR, output_filename)
        
        print(f"\nProcessing: {filename}")
        
        # Convert the logo
        success, message = convert_logo(input_path, output_path)
        
        if success:
            print(f"  [OK] {message}")
            # Move original to DONE folder
            done_path = os.path.join(DONE_DIR, filename)
            shutil.move(input_path, done_path)
            print(f"  [OK] Moved to DONE folder")
            success_count += 1
        else:
            print(f"  [ERROR] {message}")
    
    print("\n" + "=" * 50)
    print(f"Conversion complete: {success_count}/{len(files_found)} successful")

if __name__ == "__main__":
    main()