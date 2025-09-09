# Tema Warna Kolej - Campus Hub

## 🎨 Palet Warna Kolej

Campus Hub telah disesuaikan menggunakan warna rasmi kolej anda:

### Warna Utama
- **🔵 Biru Kolej**: `#1E40AF` (Primary Color)
- **🔴 Merah Kolej**: `#DC2626` (Secondary Color)  
- **⚪ Putih**: `#FFFFFF` (Background & Text)

### Aplikasi Warna

#### 🏠 **Header & Navigation**
- Header: Putih dengan border biru
- Logo: Gradient biru ke merah
- Navigation: Hover effects dengan warna kolej

#### 🎯 **Welcome Section**
- Background: Gradient biru ke merah
- Text: Putih untuk kontras yang baik
- Stats cards: Putih dengan accent biru/merah

#### 📊 **Dashboard Cards**
- Card borders: Alternating biru dan merah
- Icons: Warna biru dan merah bergantian
- Hover effects: Sesuai dengan warna card

#### 📅 **Calendar Events**
- Event dates: Gradient biru ke merah
- Text: Putih untuk keterbacaan
- Hover: Biru terang

#### 📚 **Course Materials**
- Course icons: Background biru/merah bergantian
- Progress indicators: Hijau untuk success
- Links: Warna biru kolej

#### 🎯 **Campus Services**
- Service icons: Biru dan merah alternating
- Hover effects: Sesuai dengan warna icon
- Background: Light blue/red pada hover

#### 📢 **News & Announcements**
- Category tags: Biru untuk Academic, Merah untuk Events
- Borders: Subtle gray untuk pemisahan
- Links: Biru kolej

#### 🔗 **Quick Links**
- Icons: Biru dan merah bergantian
- Hover: Background sesuai warna icon
- Border: Matching dengan icon color

#### 🦶 **Footer**
- Background: Dark gray dengan border biru
- Social links: Hover biru dan merah alternating
- Text: Putih dan gray untuk hierarchy

## 🛠️ Customization CSS

Warna boleh disesuaikan dengan mudah melalui CSS variables:

```css
:root {
    /* Warna Kolej */
    --primary-color: #1E40AF;     /* Biru Kolej */
    --secondary-color: #DC2626;   /* Merah Kolej */
    --white: #FFFFFF;             /* Putih */
    
    /* Variasi Warna */
    --primary-light: #DBEAFE;     /* Biru Muda */
    --secondary-light: #FEE2E2;   /* Merah Muda */
    --primary-hover: #1D4ED8;     /* Biru Gelap */
    --secondary-hover: #B91C1C;   /* Merah Gelap */
}
```

## 🎨 Design Principles

### **Konsistensi**
- Penggunaan warna yang konsisten di seluruh aplikasi
- Pattern alternating untuk visual variety
- Hierarchy yang jelas dengan warna

### **Aksesibiliti**
- Kontras yang tinggi (putih pada biru/merah)
- Focus indicators yang jelas
- Readable text pada semua background

### **Brand Identity**
- Mencerminkan identiti visual kolej
- Professional appearance
- Modern dan clean design

### **User Experience**
- Hover effects yang subtle
- Visual feedback yang jelas
- Navigation yang intuitif

## 🔄 Cara Menukar Warna

Jika ingin menukar kepada warna kolej lain:

1. **Update CSS Variables** dalam `styles.css`:
```css
:root {
    --primary-color: #YourBlue;
    --secondary-color: #YourRed;
    --white: #FFFFFF;
}
```

2. **Test Kontras**: Pastikan text readable pada background
3. **Update Documentation**: Kemaskini fail ini dengan warna baru
4. **Test Accessibility**: Gunakan contrast checker tools

## 📱 Responsive Behavior

Tema warna mengekalkan konsistensi merentas semua device sizes:

- **Mobile**: Warna simplified untuk performance
- **Tablet**: Full color scheme dengan optimized spacing  
- **Desktop**: Complete visual hierarchy dengan semua effects

## ✨ Visual Effects

### **Gradients**
- Welcome card: Biru ke merah diagonal
- Logo text: Biru ke merah untuk brand impact
- Event dates: Subtle gradient untuk depth

### **Hover States**
- Cards: Lift effect dengan border color change
- Services: Background tint dengan icon color
- Links: Smooth color transitions

### **Accents**
- Card borders: Left border dengan warna kolej
- Header: Bottom border biru
- Footer: Top border untuk separation

---

**Campus Hub** - Tema warna yang mencerminkan kebanggaan kolej! 🎓🎨
