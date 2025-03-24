import ee
import folium
from streamlit_folium import st_folium
import streamlit as st
import plotly.express as px
import json
import time

# Xác thực và khởi tạo Earth Engine
ee.Initialize(project='teak-vent-437103-t3')

# Cấu hình bố cục trang
st.set_page_config(layout="wide")

# Tiêu đề chính
st.title("Phân tích chất lượng không khí - Phường Tân Bình, TP Đồng Xoài, Bình Phước")

@st.cache_data(ttl=3600)  # Cache dữ liệu trong 1 giờ
def load_data():
    # Tải FeatureCollection tanbinh và chỉ lấy hình học để giảm kích thước
    tanbinh = ee.FeatureCollection("projects/teak-vent-437103-t3/assets/tanbinh").geometry()
    
    # Load CO data
    ST5_CO = ee.ImageCollection('COPERNICUS/S5P/OFFL/L3_CO')
    image_CO = ST5_CO.filterBounds(tanbinh) \
                     .select('CO_column_number_density') \
                     .filterDate('2023-01-01', '2023-12-31') \
                     .mean() \
                     .clip(tanbinh)
    map_id_dict_CO = image_CO.getMapId({
        'min': 0,
        'max': 0.05,
        'palette': ['black', 'blue', 'purple', 'cyan', 'green', 'yellow', 'red']
    })
    
    # Load NO2 data
    ST5_NO2 = ee.ImageCollection('COPERNICUS/S5P/OFFL/L3_NO2')
    image_NO2 = ST5_NO2.filterBounds(tanbinh) \
                     .select('tropospheric_NO2_column_number_density') \
                     .filterDate('2023-01-01', '2023-12-31') \
                     .mean() \
                     .clip(tanbinh)
    map_id_dict_NO2 = image_NO2.getMapId({
        'min': 0,
        'max': 0.0002,
        'palette': ['black', 'blue', 'purple', 'cyan', 'green', 'yellow', 'red']
    })
    
    # Load HCHO data
    ST5_HCHO = ee.ImageCollection('COPERNICUS/S5P/OFFL/L3_HCHO')
    image_HCHO = ST5_HCHO.filterBounds(tanbinh) \
                     .select('tropospheric_HCHO_column_number_density') \
                     .filterDate('2023-01-01', '2023-12-31') \
                     .mean() \
                     .clip(tanbinh)
    map_id_dict_HCHO = image_HCHO.getMapId({
        'min': 0.0,
        'max': 0.0003,
        'palette': ['black', 'blue', 'purple', 'cyan', 'green', 'yellow', 'red']
    })
    
    return tanbinh, map_id_dict_CO, image_CO, map_id_dict_NO2, image_NO2, map_id_dict_HCHO, image_HCHO

# Hiển thị thanh tiến trình khi đang tải dữ liệu
with st.spinner('Đang tải dữ liệu từ Google Earth Engine...'):
    tanbinh, map_id_dict_CO, image_CO, map_id_dict_NO2, image_NO2, map_id_dict_HCHO, image_HCHO = load_data()

# Chia layout thành hai cột: bản đồ bên trái, thông tin bên phải
col_map, col_info = st.columns([3, 2])

with col_map:
    # Tạo bản đồ với folium - Tọa độ cho phường Tân Bình, TP Đồng Xoài, Bình Phước
    m = folium.Map(location=[11.5353, 106.8799], zoom_start=14)

    # Thêm lớp CO vào bản đồ
    folium.TileLayer(
        tiles=map_id_dict_CO['tile_fetcher'].url_format,
        attr='Google Earth Engine',
        overlay=True,
        name='S5P CO',
        show=True
    ).add_to(m)

    # Thêm lớp NO2 vào bản đồ
    folium.TileLayer(
        tiles=map_id_dict_NO2['tile_fetcher'].url_format,
        attr='Google Earth Engine',
        overlay=True,
        name='S5P NO2',
        show=False
    ).add_to(m)

    # Thêm lớp HCHO vào bản đồ
    folium.TileLayer(
        tiles=map_id_dict_HCHO['tile_fetcher'].url_format,
        attr='Google Earth Engine',
        overlay=True,
        name='S5P HCHO',
        show=False
    ).add_to(m)

    # Thêm lớp FeatureCollection vào bản đồ
    try:
        # Sử dụng cách an toàn để lấy GeoJSON từ geometry
        tanbinh_geojson = tanbinh.getInfo()
        folium.GeoJson(
            tanbinh_geojson,
            name='Phường Tân Bình'
        ).add_to(m)
    except Exception as e:
        st.warning(f"Không thể hiển thị ranh giới Phường Tân Bình: {str(e)}")

    # Thêm công cụ vẽ
    draw = folium.plugins.Draw(export=True)
    m.add_child(draw)

    # Thêm chú thích cho bản đồ
    folium.LayerControl().add_to(m)

    st.write("👉 Click vào bản đồ để xem nồng độ khí tại vị trí đó")
    
    # Hiển thị bản đồ và lấy dữ liệu tương tác
    st_data = st_folium(m, width=800, height=600)

with col_info:
    # Chứa thông tin kết quả phân tích
    st.subheader("Thông tin phân tích")
    
    # Caching cho sample point data
    @st.cache_data(ttl=3600)  # Cache 1 giờ
    def get_point_data(lng, lat):
        clicked_point = ee.Geometry.Point([lng, lat])
        
        # Lấy giá trị CO, NO2, HCHO cùng lúc
        co_value = image_CO.sample(region=clicked_point, scale=1000, geometries=True).first().get('CO_column_number_density').getInfo()
        no2_value = image_NO2.sample(region=clicked_point, scale=1000, geometries=True).first().get('tropospheric_NO2_column_number_density').getInfo()
        hcho_value = image_HCHO.sample(region=clicked_point, scale=1000, geometries=True).first().get('tropospheric_HCHO_column_number_density').getInfo()
        
        return co_value, no2_value, hcho_value
    
    # Kiểm tra nếu người dùng click vào bản đồ
    if st_data.get('last_clicked'):
        # Lấy tọa độ của điểm đã click
        clicked_lat = st_data['last_clicked']['lat']
        clicked_lng = st_data['last_clicked']['lng']
        
        # Hiển thị phần tiêu đề kết quả
        st.markdown(f"#### 📌 Nồng độ khí tại vị trí đã chọn")
        st.markdown(f"**Tọa độ**: {clicked_lat:.4f}, {clicked_lng:.4f}")
        
        # Hiển thị thanh tiến trình cho việc lấy dữ liệu điểm
        with st.spinner('Đang phân tích dữ liệu...'):
            co_value, no2_value, hcho_value = get_point_data(clicked_lng, clicked_lat)
        
        # Hiển thị kết quả
        col1, col2, col3 = st.columns(3)
        with col1:
            st.metric(
                label="CO (mol/m²)",
                value=f"{co_value:.6f}" if co_value is not None else "Không có dữ liệu",
                delta=None
            )
        with col2:
            st.metric(
                label="NO2 (mol/m²)",
                value=f"{no2_value:.6f}" if no2_value is not None else "Không có dữ liệu",
                delta=None
            )
        with col3:
            st.metric(
                label="HCHO (mol/m²)",
                value=f"{hcho_value:.6f}" if hcho_value is not None else "Không có dữ liệu",
                delta=None
            )
    
    # Hiển thị thông tin khu vực được vẽ
    if st_data['last_active_drawing'] is not None:
        st.markdown("---")
        st.markdown("#### 📍 Phân tích khu vực được chọn")
        
        # Cache cho việc phân tích vùng
        @st.cache_data(ttl=3600)
        def analyze_region(geojson_str):
            drawn_geojson = json.loads(geojson_str)
            drawn_feature = ee.Feature(ee.Geometry(drawn_geojson))
            
            # Phân tích CO
            selected_image_co = image_CO.clip(drawn_feature.geometry())
            mean_co = selected_image_co.reduceRegion(
                reducer=ee.Reducer.mean(),
                geometry=drawn_feature.geometry(),
                scale=1000,
                maxPixels=1e13
            ).getInfo()
            mean_co_value = mean_co.get('CO_column_number_density', 'Không có dữ liệu')
            
            # Phân tích NO2
            selected_image_no2 = image_NO2.clip(drawn_feature.geometry())
            mean_no2 = selected_image_no2.reduceRegion(
                reducer=ee.Reducer.mean(),
                geometry=drawn_feature.geometry(),
                scale=1000,
                maxPixels=1e13
            ).getInfo()
            mean_no2_value = mean_no2.get('tropospheric_NO2_column_number_density', 'Không có dữ liệu')
            
            # Phân tích HCHO
            selected_image_hcho = image_HCHO.clip(drawn_feature.geometry())
            mean_hcho = selected_image_hcho.reduceRegion(
                reducer=ee.Reducer.mean(),
                geometry=drawn_feature.geometry(),
                scale=1000,
                maxPixels=1e13
            ).getInfo()
            mean_hcho_value = mean_hcho.get('tropospheric_HCHO_column_number_density', 'Không có dữ liệu')
            
            return mean_co_value, mean_no2_value, mean_hcho_value
        
        # Chuyển geojson sang chuỗi để có thể cache
        geojson_str = json.dumps(st_data['last_active_drawing']['geometry'])
        
        # Hiển thị tiến trình khi phân tích khu vực
        with st.spinner('Đang phân tích khu vực được chọn...'):
            mean_co_value, mean_no2_value, mean_hcho_value = analyze_region(geojson_str)
        
        col1, col2, col3 = st.columns(3)
        with col1:
            st.metric(
                label="CO (mol/m²)", 
                value=f"{mean_co_value:.6f}" if isinstance(mean_co_value, float) else mean_co_value,
                delta=None
            )
        with col2:
            st.metric(
                label="NO2 (mol/m²)", 
                value=f"{mean_no2_value:.6f}" if isinstance(mean_no2_value, float) else mean_no2_value,
                delta=None
            )
        with col3:
            st.metric(
                label="HCHO (mol/m²)", 
                value=f"{mean_hcho_value:.6f}" if isinstance(mean_hcho_value, float) else mean_hcho_value,
                delta=None
            )
    
    # Thêm giải thích cho người dùng
    st.markdown("---")
    st.markdown("### Hướng dẫn sử dụng")
    st.markdown("""
    - **Click vào bản đồ**: Hiển thị giá trị nồng độ khí tại điểm đó
    - **Sử dụng công cụ vẽ**: Vẽ một khu vực để phân tích giá trị trung bình
    - **Chuyển đổi lớp**: Sử dụng bảng điều khiển lớp ở góc phải bản đồ để chuyển đổi giữa các loại khí
    """)

# Tính giá trị trung bình theo tháng cho năm 2023
@st.cache_data(ttl=43200)  # Lưu cache 12 giờ
def monthly_mean(year, collection_name, band_name, _geometry):
    months = ee.List.sequence(1, 12)
    collection = ee.ImageCollection(collection_name)

    return ee.FeatureCollection(months.map(lambda month: ee.Feature(None, {
        'month': month,
        'mean': collection.filterBounds(_geometry)
                         .filterDate(ee.Date.fromYMD(year, month, 1), ee.Date.fromYMD(year, month, 1).advance(1, 'month'))
                         .select(band_name)
                         .mean()
                         .reduceRegion(
                             reducer=ee.Reducer.mean(),
                             geometry=_geometry,
                             scale=1000,
                             maxPixels=1e13
                         ).get(band_name)
    })))

# Tạo các biểu đồ trung bình theo tháng (với cache)
with st.spinner('Đang tạo biểu đồ theo tháng...'):
    monthly_means_2023_co = monthly_mean(2023, 'COPERNICUS/S5P/OFFL/L3_CO', 'CO_column_number_density', tanbinh).getInfo()
    monthly_means_2023_no2 = monthly_mean(2023, 'COPERNICUS/S5P/OFFL/L3_NO2', 'tropospheric_NO2_column_number_density', tanbinh).getInfo()
    monthly_means_2023_hcho = monthly_mean(2023, 'COPERNICUS/S5P/OFFL/L3_HCHO', 'tropospheric_HCHO_column_number_density', tanbinh).getInfo()

# Tab để hiển thị các biểu đồ
tab1, tab2, tab3 = st.tabs(["Nồng độ CO", "Nồng độ NO2", "Nồng độ HCHO"])

with tab1:
    # CO Chart
    months = [feature['properties']['month'] for feature in monthly_means_2023_co['features']]
    mean_co_values = [feature['properties']['mean'] for feature in monthly_means_2023_co['features']]
    fig_co = px.line(x=months, y=mean_co_values, labels={'x': 'Tháng', 'y': 'Giá trị CO (mol/m^2)'}, title='Giá trị trung bình CO theo tháng năm 2023 tại Phường Tân Bình, TP Đồng Xoài')
    st.plotly_chart(fig_co, use_container_width=True)

with tab2:
    # NO2 Chart
    mean_no2_values = [feature['properties']['mean'] for feature in monthly_means_2023_no2['features']]
    fig_no2 = px.line(x=months, y=mean_no2_values, labels={'x': 'Tháng', 'y': 'Giá trị NO2 (mol/m^2)'}, title='Giá trị trung bình NO2 theo tháng năm 2023 tại Phường Tân Bình, TP Đồng Xoài')
    st.plotly_chart(fig_no2, use_container_width=True)

with tab3:
    # HCHO Chart
    mean_hcho_values = [feature['properties']['mean'] for feature in monthly_means_2023_hcho['features']]
    fig_hcho = px.line(x=months, y=mean_hcho_values, labels={'x': 'Tháng', 'y': 'Giá trị HCHO (mol/m^2)'}, title='Giá trị trung bình HCHO theo tháng năm 2023 tại Phường Tân Bình, TP Đồng Xoài')
    st.plotly_chart(fig_hcho, use_container_width=True)
