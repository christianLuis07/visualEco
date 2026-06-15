"""
Pemetaan label ImageNet (MobileNetV2) -> 5 kategori sampah internal Visueco.

Setiap kategori memuat:
  - id           : sinkron dengan tabel waste_categories di MySQL
  - name         : nama kategori
  - is_recyclable: apakah dapat didaur ulang (Organik = False)
  - keywords     : substring label ImageNet yang menandakan kategori ini
  - instructions : panduan penanganan yang dikembalikan ke frontend
"""

CATEGORIES = {
    1: {
        "name": "Plastik",
        "is_recyclable": True,
        "keywords": [
            "water_bottle", "pop_bottle", "plastic_bag", "packet",
            "shopping_basket", "bucket", "soap_dispenser", "lotion",
            "pill_bottle", "milk_can", "container",
        ],
        "instructions": [
            "Kosongkan isi wadah",
            "Lepaskan label jika memungkinkan",
            "Remas untuk menghemat ruang",
            "Masukkan ke tempat sampah plastik",
        ],
    },
    2: {
        "name": "Kertas",
        "is_recyclable": True,
        "keywords": [
            "carton", "envelope", "notebook", "paper_towel", "book_jacket",
            "binder", "menu", "comic_book", "newspaper", "packet_paper",
            "toilet_tissue", "carton_box",
        ],
        "instructions": [
            "Pastikan kertas kering",
            "Lipat rapi agar tidak terbang",
            "Pisahkan dari kertas berlaminasi",
            "Masukkan ke tempat sampah kertas",
        ],
    },
    3: {
        "name": "Logam",
        "is_recyclable": True,
        "keywords": [
            "tin_can", "beer_can", "soda_can", "can", "nail", "can_opener",
            "frying_pan", "pot", "wok", "ladle", "spatula", "screw",
            "padlock", "safety_pin",
        ],
        "instructions": [
            "Kosongkan isi kaleng",
            "Bilas ringan jika kotor",
            "Tekan rata jika memungkinkan",
            "Masukkan ke tempat sampah logam",
        ],
    },
    4: {
        "name": "Kaca",
        "is_recyclable": True,
        "keywords": [
            "wine_bottle", "beer_glass", "goblet", "vase", "glass",
            "beaker", "measuring_cup", "perfume", "cocktail_shaker",
        ],
        "instructions": [
            "Kosongkan isi botol",
            "Bilas ringan",
            "Bungkus jika pecah untuk keamanan",
            "Masukkan ke tempat sampah kaca",
        ],
    },
    5: {
        "name": "Organik",
        "is_recyclable": False,
        "keywords": [
            "banana", "orange", "apple", "corn", "broccoli", "cucumber",
            "lemon", "pineapple", "strawberry", "mushroom", "cabbage",
            "cauliflower", "bell_pepper", "fig", "pomegranate", "artichoke",
            "zucchini", "spaghetti_squash", "acorn_squash", "butternut_squash",
            "head_cabbage", "granny_smith", "ear",
        ],
        "instructions": [
            "Pisahkan dari plastik atau kemasan",
            "Potong kecil-kecil jika besar",
            "Masukkan ke tempat kompos",
            "Jangan campur dengan sampah anorganik",
        ],
    },
}


def match_category(label: str):
    """
    Cari kategori pertama yang keyword-nya cocok dengan label ImageNet.
    Mengembalikan (category_id, category_dict) atau (None, None) bila tak cocok.
    """
    normalized = label.lower()
    for cat_id, cat in CATEGORIES.items():
        for kw in cat["keywords"]:
            if kw in normalized:
                return cat_id, cat
    return None, None
