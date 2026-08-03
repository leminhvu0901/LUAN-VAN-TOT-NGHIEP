// =========================================================================
// CẤU HÌNH TÙY BIẾN HỆ THỐNG TAILWIND CSS CHO TRANG BACKEND ADMIN
// Định nghĩa: Font chữ chính (Be Vietnam Pro), bảng mã màu Material Design 3,
// kích thước, khoảng cách và bo góc đồng bộ chuẩn thiết kế.
// =========================================================================
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            fontFamily: {
                sans: ['Be Vietnam Pro', 'sans-serif'], // Thay đổi font chính sang Be Vietnam Pro theo yêu cầu
                "label-md": ["Be Vietnam Pro"],
                "body-lg": ["Be Vietnam Pro"],
                "display-lg": ["Be Vietnam Pro"],
                "body-md": ["Be Vietnam Pro"],
                "label-sm": ["Be Vietnam Pro"],
                "headline-md": ["Be Vietnam Pro"],
                "body-sm": ["Be Vietnam Pro"],
                "headline-lg": ["Be Vietnam Pro"],
                "headline-lg-mobile": ["Be Vietnam Pro"]
            },
            fontSize: {
                "label-md": ["14px", {"lineHeight": "20px", "fontWeight": "600"}],
                "body-lg": ["18px", {"lineHeight": "28px", "fontWeight": "400"}],
                "display-lg": ["48px", {"lineHeight": "60px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                "body-md": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                "label-sm": ["12px", {"lineHeight": "16px", "fontWeight": "500"}],
                "headline-md": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                "body-sm": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                "headline-lg": ["32px", {"lineHeight": "40px", "fontWeight": "700"}],
                "headline-lg-mobile": ["28px", {"lineHeight": "36px", "fontWeight": "700"}]
            },
            borderRadius: {
                "DEFAULT": "0.25rem",
                "lg": "0.5rem",
                "xl": "0.75rem",
                "full": "9999px"
            },
            spacing: {
                "container-max": "1280px",
                "md": "16px",
                "gutter": "24px",
                "sm": "12px",
                "lg": "24px",
                "margin-mobile": "16px",
                "base": "8px",
                "xs": "4px",
                "xl": "40px"
            },
            colors: {
                // --- Màu sắc giữ lại từ phiên bản cũ (để không lỗi giao diện cũ) ---
                success: '#10b981',
                'success-light': '#d1fae5',
                warning: '#f59e0b',
                'warning-light': '#fef3c7',
                danger: '#ef4444',
                'danger-light': '#fee2e2',
                info: '#3b82f6',
                'info-light': '#dbeafe',
                dark: '#1f2937',
                'gray-light': '#f3f4f6',
                'sidebar-active': '#ecfdf5',
                'sidebar-active-text': '#059669',

                // --- Bảng màu Material Design 3 mới từ Stitch ---
                "surface-container-high": "#dfe8ff",
                "surface-container": "#e8eeff",
                "primary": "#006e01",
                "on-background": "#111c2d",
                "tertiary-fixed-dim": "#c4c7ca",
                "on-secondary-container": "#596577",
                "surface-bright": "#f9f9ff",
                "on-secondary": "#ffffff",
                "tertiary-container": "#929598",
                "surface": "#f9f9ff",
                "on-secondary-fixed-variant": "#3c4859",
                "on-secondary-fixed": "#101c2c",
                "secondary-container": "#d7e3f9",
                "on-surface": "#111c2d",
                "secondary-fixed-dim": "#bbc7dc",
                "on-primary-fixed": "#002200",
                "primary-fixed-dim": "#56e245",
                "background": "#f9f9ff",
                "primary-fixed": "#77ff62",
                "outline": "#6d7b67",
                "secondary": "#535f71",
                "on-primary-fixed-variant": "#005301",
                "on-primary-container": "#003600",
                "surface-variant": "#d9e3fb",
                "surface-tint": "#006e01",
                "surface-container-highest": "#d9e3fb",
                "tertiary-fixed": "#e0e3e6",
                "outline-variant": "#bccbb4",
                "on-primary": "#ffffff",
                "error-container": "#ffdad6",
                "tertiary": "#5c5f61",
                "surface-container-low": "#f0f3ff",
                "on-tertiary-fixed-variant": "#44474a",
                "on-tertiary-fixed": "#191c1e",
                "on-surface-variant": "#3e4a38",
                "on-error": "#ffffff",
                "on-tertiary-container": "#2a2e30",
                "surface-container-lowest": "#ffffff",
                "on-error-container": "#93000a",
                "on-tertiary": "#ffffff",
                "surface-dim": "#d0daf2",
                "primary-container": "#0aad0a",
                "error": "#ba1a1a",
                "inverse-primary": "#56e245",
                "inverse-on-surface": "#ecf0ff",
                "inverse-surface": "#273143",
                "secondary-fixed": "#d7e3f9"
            }
        }
    }
}
