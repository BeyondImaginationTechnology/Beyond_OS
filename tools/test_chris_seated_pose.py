import bpy
from math import radians
out=r"C:\Users\Greg\Documents\Beyond_OS\dailybreath\assets\videos\DB_Christian_StudyV8b_Chris_Seated_Test.blend"
arm=bpy.data.objects['Chris_Rig']
arm.location=( -0.1, 2.15, -0.42)
for name,z in {'mixamorig:LeftUpLeg':-90,'mixamorig:RightUpLeg':-90,'mixamorig:LeftLeg':90,'mixamorig:RightLeg':90,'mixamorig:LeftFoot':0,'mixamorig:RightFoot':0}.items():
 b=arm.pose.bones[name]; b.rotation_mode='XYZ'; b.rotation_euler=(0,0,radians(z))
bpy.ops.wm.save_as_mainfile(filepath=out)


