import bpy
import os

scene = bpy.context.scene
scene.camera = bpy.data.objects["DB_V19_Host_and_Props_Camera"]
scene.render.resolution_x = 1280
scene.render.resolution_y = 720
scene.render.resolution_percentage = 100
scene.render.image_settings.file_format = "PNG"
scene.render.filepath = os.path.join(os.path.dirname(bpy.data.filepath), "renders", "chris-v19-asset-preview.png")
scene.frame_set(1)
bpy.ops.render.render(write_still=True)
