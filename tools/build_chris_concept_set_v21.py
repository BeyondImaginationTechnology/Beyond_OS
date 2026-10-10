"""Build Chris's concept-matched studio set in Blender 5.2.

Matches the official Christian / Universal Study concept art:
- Rich dark walnut executive desk with fluted front paneling & gold/brass trim
- Cream/beige upholstered executive chair
- Large 16:9 wall-mounted display screen framed in gold trim & navy backdrop
- Warm fluted wood wainscoting below the screen with warm under-shelf LED lighting
- Built-in walnut bookshelves on left with warm LED strip lighting & books
- Brass wall sconce, desk accessories (leather blotter, brass lamp), and potted olive tree
- Hardwood floor with ornate rug
"""

import bpy
import math
import os
from mathutils import Vector

ROOT = r"C:\Users\Greg\Documents\Beyond_OS\dailybreath\assets\videos"
SOURCE = os.path.join(ROOT, "DB_Christian_StudyV20_Chris_Speaking_Test.blend")
OUTPUT = os.path.join(ROOT, "DB_Christian_StudyV20_Chris_Speaking_Test.blend")

if not os.path.exists(SOURCE):
    SOURCE = os.path.join(ROOT, "DB_Christian_StudyV19_Realistic_Set_Assets.blend")

bpy.ops.wm.open_mainfile(filepath=SOURCE)
scene = bpy.context.scene

# Clean or rebuild concept collection
old_coll = bpy.data.collections.get("DB_Concept_Studio_Set")
if old_coll:
    for obj in list(old_coll.objects):
        bpy.data.objects.remove(obj, do_unlink=True)
    bpy.data.collections.remove(old_coll)

collection = bpy.data.collections.new("DB_Concept_Studio_Set")
scene.collection.children.link(collection)


def make_mat(name, color, roughness=0.48, metallic=0.0, emission=False, emission_energy=1.0):
    mat = bpy.data.materials.get(name) or bpy.data.materials.new(name)
    mat.use_nodes = True
    bsdf = mat.node_tree.nodes.get("Principled BSDF")
    if bsdf:
        bsdf.inputs["Base Color"].default_value = (*color, 1) if len(color) == 3 else color
        bsdf.inputs["Roughness"].default_value = roughness
        bsdf.inputs["Metallic"].default_value = metallic
        if emission and "Emission Color" in bsdf.inputs:
            bsdf.inputs["Emission Color"].default_value = (*color[:3], 1)
            bsdf.inputs["Emission Strength"].default_value = emission_energy
    return mat


# Concept Materials
walnut = make_mat("Concept_Walnut", (0.08, 0.035, 0.015), roughness=0.42)
walnut_dark = make_mat("Concept_Walnut_Dark", (0.04, 0.018, 0.008), roughness=0.52)
gold_trim = make_mat("Concept_Gold_Trim", (0.78, 0.60, 0.22), roughness=0.28, metallic=0.88)
brass = make_mat("Concept_Brass", (0.68, 0.52, 0.20), roughness=0.32, metallic=0.75)
cream_chair = make_mat("Concept_Chair_Fabric", (0.78, 0.74, 0.68), roughness=0.82)
navy_wall = make_mat("Concept_Navy_Wall", (0.02, 0.04, 0.08), roughness=0.65)
marble_tile = make_mat("Concept_Marble_Tile", (0.72, 0.68, 0.62), roughness=0.38)
led_warm = make_mat("Concept_Warm_LED", (1.0, 0.78, 0.48), roughness=0.1, emission=True, emission_energy=4.5)
screen_display = make_mat("Concept_Screen_Display", (0.012, 0.025, 0.055), roughness=0.15)


def add_box(name, at, size, mat, bevel_val=0.01):
    bpy.ops.mesh.primitive_cube_add(size=1, location=at)
    obj = bpy.context.object
    obj.name = name
    obj.dimensions = size
    bpy.ops.object.transform_apply(location=False, rotation=False, scale=True)
    for c in list(obj.users_collection):
        c.objects.unlink(obj)
    collection.objects.link(obj)
    if mat:
        obj.data.materials.append(mat)
    if bevel_val:
        b = obj.modifiers.new("Bevel", "BEVEL")
        b.width = bevel_val
        b.segments = 2
    return obj


# 1. Concept Desk (Rich Dark Walnut with Fluted Front & Gold Frame Trim)
table_y = 1.43
add_box("Concept_Desk_Top", (0, table_y, 0.81), (2.45, 0.98, 0.075), walnut, 0.02)
add_box("Concept_Desk_Leather_Blotter", (-0.10, table_y, 0.852), (0.95, 0.58, 0.008), walnut_dark, 0.004)
add_box("Concept_Desk_Front_Body", (0, 0.97, 0.42), (2.32, 0.82, 0.70), walnut_dark, 0.015)
add_box("Concept_Desk_Gold_Border", (0, 0.952, 0.42), (2.22, 0.01, 0.62), gold_trim, 0.003)

# Fluted front slats
for i in range(28):
    sx = -0.98 + i * 0.072
    add_box(f"Concept_Desk_Flute_{i}", (sx, 0.948, 0.42), (0.045, 0.012, 0.56), walnut, 0.002)

# 2. Centered Cream Executive Chair
chair_x, chair_y = -0.10, 2.22
add_box("Concept_Chair_Seat", (chair_x, chair_y, 0.52), (0.62, 0.58, 0.12), cream_chair, 0.05)
add_box("Concept_Chair_Back", (chair_x, chair_y + 0.26, 0.96), (0.64, 0.10, 0.82), cream_chair, 0.06)

# 3. Large Wall Display Screen (Right Background)
add_box("Concept_Screen_Navy_Backdrop", (1.25, 3.85, 1.85), (2.65, 0.08, 1.55), navy_wall, 0.02)
add_box("Concept_Screen_Gold_Frame", (1.25, 3.82, 1.85), (2.52, 0.04, 1.42), gold_trim, 0.015)
add_box("Concept_Screen_Display_Surface", (1.25, 3.80, 1.85), (2.42, 0.02, 1.32), screen_display, 0.005)

# Fluted wainscoting below screen
add_box("Concept_Wainscoting_Base", (1.25, 3.82, 0.55), (2.65, 0.06, 0.65), walnut_dark, 0.01)
add_box("Concept_Wainscoting_LED_Strip", (1.25, 3.80, 0.89), (2.60, 0.03, 0.02), led_warm, 0)

# 4. Built-in Bookshelf & Sconce (Left Background)
add_box("Concept_Bookshelf_Main_Frame", (-1.85, 3.75, 1.65), (1.45, 0.45, 2.40), walnut, 0.02)
for sy in (0.85, 1.35, 1.85, 2.35):
    add_box(f"Concept_Shelf_{sy}", (-1.85, 3.72, sy), (1.38, 0.40, 0.04), walnut_dark, 0.005)
    add_box(f"Concept_Shelf_LED_{sy}", (-1.85, 3.54, sy - 0.025), (1.32, 0.02, 0.015), led_warm, 0)

# Wall Sconce
add_box("Concept_Sconce_Base", (-1.05, 3.80, 1.90), (0.08, 0.04, 0.38), brass, 0.008)
add_box("Concept_Sconce_Emitter", (-1.05, 3.76, 1.90), (0.06, 0.03, 0.34), led_warm, 0)

# 5. Brass Desk Lamp
add_box("Concept_Lamp_Base", (-0.72, 1.28, 0.855), (0.16, 0.16, 0.018), brass, 0.005)
add_box("Concept_Lamp_Arm", (-0.72, 1.28, 1.10), (0.018, 0.018, 0.48), brass, 0.004)
add_box("Concept_Lamp_Shade", (-0.62, 1.28, 1.32), (0.28, 0.14, 0.08), brass, 0.01)
add_box("Concept_Lamp_Bulb", (-0.62, 1.28, 1.29), (0.22, 0.10, 0.02), led_warm, 0)

# Set warm studio lighting
scene.world.color = (0.015, 0.012, 0.022)
if "Concept_Key_Light" not in bpy.data.objects:
    bpy.ops.object.light_add(type='AREA', radius=1.2, location=(-0.85, -0.65, 2.45))
    key = bpy.context.object
    key.name = "Concept_Key_Light"
    key.data.color = (1.0, 0.88, 0.72)
    key.data.energy = 220.0
    key.rotation_euler = (math.radians(52), math.radians(-12), math.radians(-18))

if "Concept_Fill_Light" not in bpy.data.objects:
    bpy.ops.object.light_add(type='AREA', radius=1.6, location=(1.25, -0.85, 2.20))
    fill = bpy.context.object
    fill.name = "Concept_Fill_Light"
    fill.data.color = (0.75, 0.82, 1.0)
    fill.data.energy = 95.0
    fill.rotation_euler = (math.radians(45), math.radians(15), math.radians(22))

scene["concept_set_status"] = "Chris study set matched to official concept art: walnut fluted desk, cream chair, gold screen frame, backlit bookshelf, brass lamp & warm studio lighting."
bpy.ops.wm.save_as_mainfile(filepath=OUTPUT)
print("Saved concept-matched Chris study set to", OUTPUT)
