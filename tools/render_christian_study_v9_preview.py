import bpy
scene=bpy.context.scene
scene.render.engine='BLENDER_EEVEE'
scene.render.resolution_x=960
scene.render.resolution_y=540
scene.render.resolution_percentage=100
scene.render.image_settings.file_format='PNG'
scene.frame_set(66)
scene.render.filepath=r"C:\Users\Greg\Documents\Beyond_OS\dailybreath\assets\videos\DB_Christian_StudyV9_talking_preview.png"
bpy.ops.render.render(write_still=True)
