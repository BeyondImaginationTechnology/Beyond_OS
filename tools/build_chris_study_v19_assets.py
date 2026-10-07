"""Add grounded furniture and devotional props to Chris's verse set.

Run with Blender 5.2 in background mode. The source is never overwritten.
"""

import bpy
import math
import os
from mathutils import Vector

ROOT = r"C:\Users\Greg\Documents\Beyond_OS\dailybreath\assets\videos"
SOURCE = os.path.join(ROOT, "DB_Christian_StudyV18_Chris_Morning_Verse_Test.blend")
OUTPUT = os.path.join(ROOT, "DB_Christian_StudyV19_Realistic_Set_Assets.blend")

bpy.ops.wm.open_mainfile(filepath=SOURCE)
scene = bpy.context.scene
old = bpy.data.collections.get("DB_V19_Realistic_Set")
if old:
    for obj in list(old.objects):
        bpy.data.objects.remove(obj, do_unlink=True)
    bpy.data.collections.remove(old)
collection = bpy.data.collections.new("DB_V19_Realistic_Set")
scene.collection.children.link(collection)


def material(name, color, roughness=0.5, metallic=0.0, wood=False):
    mat = bpy.data.materials.get(name) or bpy.data.materials.new(name)
    mat.diffuse_color = (*color, 1)
    mat.use_nodes = True
    nodes = mat.node_tree.nodes
    bsdf = nodes.get("Principled BSDF")
    bsdf.inputs["Base Color"].default_value = (*color, 1)
    bsdf.inputs["Roughness"].default_value = roughness
    bsdf.inputs["Metallic"].default_value = metallic
    if wood:
        tex = nodes.new("ShaderNodeTexNoise")
        tex.inputs["Scale"].default_value = 7
        tex.inputs["Detail"].default_value = 4
        tex.inputs["Roughness"].default_value = 0.72
        mapping = nodes.new("ShaderNodeVectorMath")
        mapping.operation = "MULTIPLY"
        mapping.inputs[1].default_value = (1.5, 16, 1.0)
        geo = nodes.new("ShaderNodeTexCoord")
        ramp = nodes.new("ShaderNodeValToRGB")
        ramp.color_ramp.elements[0].position = 0.27
        ramp.color_ramp.elements[0].color = (*tuple(c * 0.72 for c in color), 1)
        ramp.color_ramp.elements[1].position = 0.73
        ramp.color_ramp.elements[1].color = (*tuple(min(c * 1.18, 1) for c in color), 1)
        links = mat.node_tree.links
        links.new(geo.outputs["Generated"], mapping.inputs[0])
        links.new(mapping.outputs["Vector"], tex.inputs["Vector"])
        links.new(tex.outputs["Fac"], ramp.inputs[0])
        links.new(ramp.outputs[0], bsdf.inputs["Base Color"])
    return mat


walnut = material("DB_V19_Walnut_Satin", (0.105, 0.044, 0.018), 0.48, wood=True)
walnut_dark = material("DB_V19_Walnut_Dark", (0.052, 0.023, 0.011), 0.57, wood=True)
brass = material("DB_V19_Aged_Brass", (0.54, 0.38, 0.16), 0.38, 0.68)
leather = material("DB_V19_Chair_Leather", (0.13, 0.063, 0.033), 0.76)
stitch = material("DB_V19_Chair_Stitch", (0.43, 0.34, 0.22), 0.8)
ceramic = material("DB_V19_Ceramic_Ivory", (0.83, 0.80, 0.68), 0.27)
coffee = material("DB_V19_Coffee", (0.09, 0.043, 0.018), 0.22)
paper = material("DB_V19_Paper", (0.81, 0.76, 0.62), 0.83)
gold = material("DB_V19_Gold_Foil", (0.68, 0.51, 0.20), 0.34, 0.55)
logo_ink = material("DB_V19_Logo_Forest_Ink", (0.018, 0.095, 0.052), 0.64)
print_ink = material("DB_V19_Bible_Print", (0.11, 0.09, 0.065), 0.88)


def put(obj, name, mat=None):
    obj.name = name
    for coll in list(obj.users_collection):
        coll.objects.unlink(obj)
    collection.objects.link(obj)
    if mat:
        obj.data.materials.append(mat)
    return obj


def bevel(obj, width=0.015, segments=3):
    mod = obj.modifiers.new("Soft manufactured edges", "BEVEL")
    mod.width = width
    mod.segments = segments
    mod.affect = "EDGES"
    obj.modifiers.new("Weighted corner normals", "WEIGHTED_NORMAL")
    return obj


def box(name, at, size, mat, edge=0.01):
    bpy.ops.mesh.primitive_cube_add(size=1, location=at)
    obj = put(bpy.context.object, name, mat)
    obj.dimensions = size
    bpy.ops.object.transform_apply(location=False, rotation=False, scale=True)
    if edge:
        bevel(obj, edge)
    return obj


def cylinder(name, at, radius, depth, mat, vertices=64, edge=0.005):
    bpy.ops.mesh.primitive_cylinder_add(vertices=vertices, radius=radius, depth=depth, location=at)
    obj = put(bpy.context.object, name, mat)
    if edge:
        bevel(obj, edge)
    for poly in obj.data.polygons:
        poly.use_smooth = True
    return obj


def curve_tube(name, points, radius, mat, cyclic=False):
    data = bpy.data.curves.new(name, "CURVE")
    data.dimensions = "3D"
    data.bevel_depth = radius
    data.bevel_resolution = 3
    spline = data.splines.new("POLY")
    spline.points.add(len(points) - 1)
    for point, xyz in zip(spline.points, points):
        point.co = (*xyz, 1)
    spline.use_cyclic_u = cyclic
    obj = bpy.data.objects.new(name, data)
    collection.objects.link(obj)
    data.materials.append(mat)
    return obj


def label(name, body, at, size, mat, rotation=(0, 0, 0), align="CENTER"):
    data = bpy.data.curves.new(name, "FONT")
    data.body = body
    data.size = size
    data.align_x = align
    data.extrude = 0.0008
    obj = bpy.data.objects.new(name, data)
    collection.objects.link(obj)
    obj.location = at
    obj.rotation_euler = rotation
    data.materials.append(mat)
    return obj


# Hide only the old imported desk; keep it recoverable in the source file.
old_desk = bpy.data.objects.get("ChrisStudy_Desk_Root")
if old_desk:
    old_desk.hide_render = True
    old_desk.hide_set(True)

# The imported host was staged for the lower V18 desk. Lift the whole rig to
# keep his face and upper body visible over the correctly sized new table.
host = bpy.data.objects.get("Chris_Rig")
if host:
    host.location.z += 0.34

# Host table: solid bevelled walnut top, framed apron, four tapered-looking legs,
# restrained brass pulls, and a lower cross rail. The front faces the audience at -Y.
table_y = 1.43
box("DB_Table_Solid_Walnut_Top", (0, table_y, 0.805), (2.28, 0.92, 0.065), walnut, 0.026)
box("DB_Table_Front_Apron", (0, 0.995, 0.705), (2.04, 0.038, 0.15), walnut_dark)
box("DB_Table_Rear_Apron", (0, 1.865, 0.705), (2.04, 0.038, 0.15), walnut_dark)
for x in (-1.075, 1.075):
    box("DB_Table_Side_Apron", (x, table_y, 0.705), (0.035, 0.8, 0.15), walnut_dark)
    for y in (1.06, 1.80):
        box("DB_Table_Leg", (x, y, 0.38), (0.066, 0.066, 0.74), walnut, 0.008)
        box("DB_Table_Leg_Ferrule", (x, y, 0.05), (0.067, 0.067, 0.065), brass, 0.003)
box("DB_Table_Front_Inlay", (0, 0.969, 0.705), (1.82, 0.006, 0.006), brass, 0.002)
for x in (-0.43, 0.43):
    box("DB_Table_Drawer_Face", (x, 0.969, 0.706), (0.72, 0.013, 0.12), walnut, 0.008)
    cylinder("DB_Table_Drawer_Knob", (x, 0.944, 0.708), 0.018, 0.018, brass, 24)
    bpy.context.object.rotation_euler.x = math.pi / 2

# Upholstered chair behind Chris, with a supportive seat and curved back silhouette.
chair_x, chair_y = -0.10, 2.23
box("DB_Chair_Seat_Walnut", (chair_x, chair_y, 0.45), (0.65, 0.64, 0.075), walnut_dark, 0.055)
box("DB_Chair_Seat_Leather", (chair_x, chair_y, 0.51), (0.57, 0.54, 0.085), leather, 0.06)
for x in (chair_x - 0.265, chair_x + 0.265):
    for y in (chair_y - 0.25, chair_y + 0.25):
        box("DB_Chair_Leg", (x, y, 0.22), (0.048, 0.048, 0.42), walnut_dark, 0.009)
box("DB_Chair_Back_Walnut", (chair_x, chair_y + 0.29, 0.91), (0.66, 0.072, 0.86), walnut_dark, 0.07)
box("DB_Chair_Back_Leather", (chair_x, chair_y + 0.247, 0.93), (0.54, 0.037, 0.70), leather, 0.065)
for x in (chair_x - 0.25, chair_x + 0.25):
    box("DB_Chair_Arm_Walnut", (x, chair_y - 0.02, 0.74), (0.063, 0.50, 0.055), walnut, 0.026)
    box("DB_Chair_Arm_Leather_Pad", (x, chair_y - 0.035, 0.775), (0.073, 0.36, 0.032), leather, 0.016)
    box("DB_Chair_Arm_Support", (x, chair_y - 0.19, 0.62), (0.048, 0.046, 0.23), walnut_dark)

# Functional looking hollow cup with rolled rim, dark coffee, handle and DB emblem.
cup_x, cup_y = -0.78, 1.31
verts, faces = [], []
profile = [(0.080, 0.836), (0.092, 0.84), (0.101, 0.85), (0.111, 0.95),
           (0.118, 1.04), (0.113, 1.049), (0.101, 1.044), (0.096, 0.96),
           (0.088, 0.865), (0.075, 0.857)]
segments = 64
for r, z in profile:
    for i in range(segments):
        angle = 2 * math.pi * i / segments
        verts.append((cup_x + r * math.cos(angle), cup_y + r * math.sin(angle), z))
for j in range(len(profile) - 1):
    for i in range(segments):
        a = j * segments + i
        b = j * segments + (i + 1) % segments
        faces.append((a, b, b + segments, a + segments))
mesh = bpy.data.meshes.new("DB_Mug_Ceramic_Mesh")
mesh.from_pydata(verts, [], faces)
mesh.update()
mug = bpy.data.objects.new("DB_Mug_Ivory_Ceramic", mesh)
collection.objects.link(mug)
mesh.materials.append(ceramic)
for poly in mesh.polygons:
    poly.use_smooth = True
cylinder("DB_Mug_Coffee_Surface", (cup_x, cup_y, 1.016), 0.096, 0.003, coffee, 64, 0)
handle = []
for i in range(21):
    t = -math.pi / 2 + math.pi * i / 20
    handle.append((cup_x + 0.119 + 0.075 * math.cos(t), cup_y, 0.946 + 0.086 * math.sin(t)))
curve_tube("DB_Mug_Ceramic_Handle", handle, 0.017, ceramic)
label("DB_Mug_Logo", "DB", (cup_x, cup_y - 0.117, 0.922), 0.06, logo_ink,
      (math.pi / 2, 0, 0))
label("DB_Mug_Wordmark", "DAILY BREATH", (cup_x, cup_y - 0.117, 0.882), 0.014, logo_ink,
      (math.pi / 2, 0, 0))

# Three distinct closed hardcover reference books. Their covers use modest
# ornament and plain identifying text, avoiding invented religious iconography.
book_specs = [
    ("Torah", 0.29, 1.30, (0.12, 0.055, 0.025), "THE\nTORAH"),
    ("Quran", 0.81, 1.30, (0.025, 0.105, 0.065), "THE\nQURAN"),
]
for short, x, y, cover_color, title in book_specs:
    cover = material("DB_V19_" + short + "_Cover", cover_color, 0.66)
    box("DB_" + short + "_Page_Block", (x, y, 0.856), (0.405, 0.51, 0.049), paper, 0.005)
    box("DB_" + short + "_Bottom_Cover", (x, y, 0.825), (0.45, 0.55, 0.013), cover, 0.009)
    box("DB_" + short + "_Top_Cover", (x, y, 0.888), (0.45, 0.55, 0.015), cover, 0.009)
    box("DB_" + short + "_Spine", (x - 0.218, y, 0.857), (0.03, 0.55, 0.076), cover, 0.007)
    # Front edge gold rules and cover typography face upward in close shots.
    for yy in (y - 0.23, y + 0.23):
        box("DB_" + short + "_Gold_Rule", (x, yy, 0.899), (0.34, 0.003, 0.001), gold, 0)
    label("DB_" + short + "_Cover_Title", title, (x, y - 0.07, 0.901), 0.067, gold)
    label("DB_" + short + "_Cover_Subtitle", "DAILY BREATH LIBRARY",
          (x, y - 0.185, 0.901), 0.013, gold)
    # Page edges with fine, irregular strata read better than a flat beige block.
    for j in range(5):
        box("DB_" + short + "_Page_Edge", (x + 0.205, y, 0.837 + j * 0.008),
            (0.0015, 0.475, 0.0013), paper, 0)

# Chris reads from an open Bible. Curved page meshes and layered edges catch
# studio light more naturally than a flat rectangular block.
bible_x, bible_y = 0.34, 1.35
bible_cover = material("DB_V19_Bible_Navy_Leather", (0.025, 0.052, 0.10), 0.72)
box("DB_Bible_Open_Cover", (bible_x, bible_y, 0.851), (0.83, 0.53, 0.022), bible_cover, 0.035)
box("DB_Bible_Center_Spine", (bible_x, bible_y, 0.875), (0.046, 0.52, 0.035), bible_cover, 0.014)


def bible_page(name, side, layer):
    verts, faces = [], []
    rows, cols = 5, 16
    for row in range(rows):
        yy = bible_y - 0.235 + 0.47 * row / (rows - 1)
        for col in range(cols):
            radial = 0.018 + 0.375 * col / (cols - 1)
            xx = bible_x + side * radial
            rise = 0.035 * (col / (cols - 1)) ** 1.55
            # Gentle sweep at the fore edge, as in a bound paper stack.
            zz = 0.877 + layer * 0.003 + rise + 0.004 * math.sin(math.pi * row / (rows - 1))
            verts.append((xx, yy, zz))
    for row in range(rows - 1):
        for col in range(cols - 1):
            a = row * cols + col
            faces.append((a, a + 1, a + 1 + cols, a + cols))
    mesh = bpy.data.meshes.new(name + "_Mesh")
    mesh.from_pydata(verts, [], faces)
    mesh.update()
    obj = bpy.data.objects.new(name, mesh)
    collection.objects.link(obj)
    mesh.materials.append(paper)
    for poly in mesh.polygons:
        poly.use_smooth = True


for side, word in ((-1, "Left"), (1, "Right")):
    inner_x = bible_x + side * 0.018
    outer_x = bible_x + side * 0.393
    front_y, back_y = bible_y - 0.235, bible_y + 0.235
    block_verts = [
        (inner_x, front_y, 0.877), (outer_x, front_y, 0.912),
        (outer_x, back_y, 0.912), (inner_x, back_y, 0.877),
        (inner_x, front_y, 0.856), (outer_x, front_y, 0.856),
        (outer_x, back_y, 0.856), (inner_x, back_y, 0.856),
    ]
    block_faces = [(0, 1, 2, 3), (4, 7, 6, 5), (0, 4, 5, 1),
                   (1, 5, 6, 2), (2, 6, 7, 3), (3, 7, 4, 0)]
    block_mesh = bpy.data.meshes.new("DB_Bible_" + word + "_Page_Block_Mesh")
    block_mesh.from_pydata(block_verts, [], block_faces)
    block_mesh.update()
    block = bpy.data.objects.new("DB_Bible_" + word + "_Page_Block", block_mesh)
    collection.objects.link(block)
    block_mesh.materials.append(paper)
    for layer in range(4):
        bible_page("DB_Bible_" + word + "_Page_Layer_" + str(layer), side, layer)
    for line in range(9):
        yy = bible_y - 0.175 + 0.039 * line
        length = 0.235 if line % 3 else 0.18
        xx = bible_x + side * (0.20 + (0.235 - length) * 0.5)
        height = 0.89 + 0.035 * (0.20 / 0.393) ** 1.55
        box("DB_Bible_Print_" + word, (xx, yy, height),
            (length, 0.0022, 0.0006), print_ink, 0)
label("DB_Bible_Isaiah_Reference", "ISAIAH 26", (bible_x + 0.18, bible_y + 0.19, 0.917),
      0.024, print_ink)
curve_tube("DB_Bible_Ribbon", [(bible_x, bible_y - 0.26, 0.89),
                                (bible_x + 0.02, bible_y - 0.34, 0.864)],
           0.006, brass)

# Preserve the two other book assets for their own future studios, but keep
# them out of both the viewport and rendered Christian studio shots.
library = bpy.data.collections.new("DB_V19_Future_Studio_Book_Assets_HIDDEN")
scene.collection.children.link(library)
for obj in list(collection.objects):
    if obj.name.startswith(("DB_Torah_", "DB_Quran_")):
        collection.objects.unlink(obj)
        library.objects.link(obj)
        obj.hide_render = True
        obj.hide_set(True)

# A practical review shot for the new details; the episode's V18 camera
# animation is left intact until the broader direction is approved.
camera_data = bpy.data.cameras.new("DB_V19_Host_and_Props_Lens")
camera_data.lens = 54
camera = bpy.data.objects.new("DB_V19_Host_and_Props_Camera", camera_data)
collection.objects.link(camera)
camera.location = (0.0, -2.7, 1.64)
target = Vector((0.0, 1.52, 1.08))
camera.rotation_euler = (target - camera.location).to_track_quat("-Z", "Y").to_euler()

# Pull the brightly lit V18 set toward a calmer studio exposure.
scene.view_settings.exposure = -0.45

scene["v19_asset_note"] = "Chris studio: table, upholstered chair, DB mug, Bible only. Torah and Quran assets hidden for future studios."
scene["v19_source"] = os.path.basename(SOURCE)
bpy.ops.wm.save_as_mainfile(filepath=OUTPUT)
print("SAVED_V19", OUTPUT, "ASSET_COUNT", len(collection.objects))
